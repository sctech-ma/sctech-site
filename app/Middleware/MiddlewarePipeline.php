<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Container;
use SCTech\Core\Request;
use SCTech\Core\Response;
use RuntimeException;

final class MiddlewarePipeline
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param list<MiddlewareInterface|callable|string> $middleware
     */
    public function process(Request $request, array $middleware, RequestHandlerInterface $destination): Response
    {
        $next = array_reduce(
            array_reverse($middleware),
            fn (RequestHandlerInterface $next, mixed $item): RequestHandlerInterface =>
                new CallableRequestHandler(
                    fn (Request $request): Response => $this->invoke($item, $request, $next)
                ),
            $destination
        );

        return $next->handle($request);
    }

    private function invoke(mixed $middleware, Request $request, RequestHandlerInterface $next): Response
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $next);
        }

        if (is_callable($middleware)) {
            $response = $this->container->call($middleware, [
                'request' => $request,
                'next' => $next,
            ]);

            if ($response instanceof Response) {
                return $response;
            }
        }

        throw new RuntimeException('Middleware must implement MiddlewareInterface or return a Response.');
    }
}

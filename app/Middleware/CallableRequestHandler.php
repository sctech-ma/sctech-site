<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use Closure;
use SCTech\Core\Request;
use SCTech\Core\Response;

final class CallableRequestHandler implements RequestHandlerInterface
{
    private readonly Closure $handler;

    /** @param callable(Request): Response $handler */
    public function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}

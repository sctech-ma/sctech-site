<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use ArrayObject;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Middleware\MiddlewareInterface;
use SCTech\Middleware\RequestHandlerInterface;

final class RecordingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $name,
        private readonly ArrayObject $events
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->events[] = $this->name . ':before';
        $response = $next->handle($request);
        $this->events[] = $this->name . ':after';

        return $response;
    }
}

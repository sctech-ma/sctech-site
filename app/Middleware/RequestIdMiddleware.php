<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $candidate = (string) $request->header('X-Request-ID', '');
        $requestId = preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{7,79}$/D', $candidate) === 1
            ? $candidate
            : bin2hex(random_bytes(16));

        $response = $next->handle($request->withAttribute('request_id', $requestId));

        return $response->withHeader('X-Request-ID', $requestId);
    }
}

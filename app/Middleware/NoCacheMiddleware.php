<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

final class NoCacheMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        return $next->handle($request)
            ->withHeader('Cache-Control', 'no-store, private')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }
}

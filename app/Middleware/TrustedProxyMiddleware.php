<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

final class TrustedProxyMiddleware implements MiddlewareInterface
{
    /** @param list<string> $trustedProxies */
    public function __construct(private readonly array $trustedProxies = [])
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        return $next->handle($request->withAttributes([
            'client_ip' => $request->clientIp($this->trustedProxies),
            'is_secure' => $request->isSecure($this->trustedProxies),
        ]));
    }
}

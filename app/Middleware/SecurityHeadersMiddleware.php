<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /** @param list<string> $trustedProxies */
    public function __construct(
        private readonly bool $hstsEnabled = false,
        private readonly array $trustedProxies = [],
        private readonly bool $upgradeInsecureRequests = false
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        $request = $request->withAttribute('csp_nonce', $nonce);
        $response = $next->handle($request);

        $policy = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "media-src 'self'",
            "manifest-src 'self'",
        ];
        if ($this->upgradeInsecureRequests) {
            $policy[] = 'upgrade-insecure-requests';
        }

        $response = $response
            ->withHeader('Content-Security-Policy', implode('; ', $policy))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin');

        if ($this->hstsEnabled && $request->isSecure($this->trustedProxies)) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

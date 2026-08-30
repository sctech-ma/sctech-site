<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Core\Csrf;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;
use SCTech\Middleware\CallableRequestHandler;
use SCTech\Middleware\CsrfMiddleware;
use SCTech\Middleware\SecurityHeadersMiddleware;
use SCTech\Middleware\TrustedProxyMiddleware;

final class SecurityMiddlewareTest extends TestCase
{
    public function testSecurityHeadersContainARequestScopedNonce(): void
    {
        $middleware = new SecurityHeadersMiddleware(true, ['10.0.0.0/8'], true);
        $request = Request::create('GET', '/', [], [], ['X-Forwarded-Proto' => 'https'], [
            'REMOTE_ADDR' => '10.0.0.10',
        ]);
        $handler = new CallableRequestHandler(static function (Request $request): Response {
            return Response::html((string) $request->attribute('csp_nonce'));
        });

        $response = $middleware->process($request, $handler);

        self::assertNotSame('', $response->body());
        self::assertStringContainsString(
            "'nonce-" . $response->body() . "'",
            $response->headerLine('Content-Security-Policy')
        );
        self::assertSame('DENY', $response->headerLine('X-Frame-Options'));
        self::assertStringContainsString('max-age=31536000', $response->headerLine('Strict-Transport-Security'));
    }

    public function testCsrfMiddlewareRejectsUnsafeRequestsWithoutAValidToken(): void
    {
        $session = Session::memory();
        $csrf = new Csrf($session);
        $csrf->token();
        $middleware = new CsrfMiddleware($csrf);
        $handler = new CallableRequestHandler(static fn (): Response => Response::text('accepted'));

        $rejected = $middleware->process(Request::create('POST', '/contact'), $handler);
        self::assertSame(419, $rejected->status());

        $accepted = $middleware->process(
            Request::create('POST', '/contact', [], ['_token' => $csrf->token()]),
            $handler
        );
        self::assertSame(200, $accepted->status());
    }

    public function testTrustedProxyMiddlewarePublishesOnlyVerifiedClientMetadata(): void
    {
        $middleware = new TrustedProxyMiddleware(['10.0.0.0/8']);
        $request = Request::create('GET', '/', [], [], [
            'X-Forwarded-For' => '203.0.113.25, 10.1.0.8',
            'X-Forwarded-Proto' => 'https',
        ], ['REMOTE_ADDR' => '10.0.0.4']);
        $handler = new CallableRequestHandler(static fn (Request $request): Response => Response::json([
            'ip' => $request->attribute('client_ip'),
            'secure' => $request->attribute('is_secure'),
        ]));

        $response = $middleware->process($request, $handler);

        self::assertJsonStringEqualsJsonString(
            '{"ip":"203.0.113.25","secure":true}',
            $response->body()
        );
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SCTech\Core\Request;
use SCTech\Core\Response;

final class RequestResponseTest extends TestCase
{
    public function testForwardedAddressesAreIgnoredUnlessTheImmediateProxyIsTrusted(): void
    {
        $request = Request::create('GET', '/', [], [], [
            'X-Forwarded-For' => '203.0.113.20',
            'X-Forwarded-Proto' => 'https',
        ], ['REMOTE_ADDR' => '10.0.0.4']);

        self::assertSame('10.0.0.4', $request->clientIp());
        self::assertFalse($request->isSecure());
        self::assertSame('203.0.113.20', $request->clientIp(['10.0.0.0/8']));
        self::assertTrue($request->isSecure(['10.0.0.0/8']));
    }

    public function testTrustedProxyChainCannotBeSpoofedFromTheLeft(): void
    {
        $request = Request::create('GET', '/', [], [], [
            'X-Forwarded-For' => '198.51.100.99, 203.0.113.20, 10.1.0.8',
        ], ['REMOTE_ADDR' => '10.0.0.4']);

        self::assertSame('203.0.113.20', $request->clientIp(['10.0.0.0/8']));
    }

    public function testRequestMutationReturnsANewInstance(): void
    {
        $request = Request::create('POST', '/contact', [], ['name' => 'Karim']);
        $changed = $request->withAttribute('request_id', 'request-123');

        self::assertNull($request->attribute('request_id'));
        self::assertSame('request-123', $changed->attribute('request_id'));
        self::assertSame('Karim', $changed->input('name'));
    }

    public function testResponseRejectsHeaderInjectionAndOpenRedirects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Response::redirect("/safe\r\nX-Evil: injected");
    }

    public function testResponseAllowsOnlyWhitelistedAbsoluteRedirectHosts(): void
    {
        $response = Response::redirect('https://sctech.ma/contact', 302, [], ['sctech.ma']);
        self::assertSame('https://sctech.ma/contact', $response->headerLine('Location'));

        $this->expectException(InvalidArgumentException::class);
        Response::redirect('https://attacker.example/login', 302, [], ['sctech.ma']);
    }

    public function testDetectBasePathUnderSubdirectory(): void
    {
        $serverVirtualHost = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/solutions',
            'SCRIPT_NAME' => '/index.php',
        ];
        $requestVirtualHost = Request::fromGlobals($serverVirtualHost);
        self::assertSame('', $requestVirtualHost->basePath());
        self::assertSame('/solutions', $requestVirtualHost->path());

        $serverSubdir = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/sctech-site/solutions',
            'SCRIPT_NAME' => '/sctech-site/public/index.php',
        ];
        $requestSubdir = Request::fromGlobals($serverSubdir);
        self::assertSame('/sctech-site', $requestSubdir->basePath());
        self::assertSame('/solutions', $requestSubdir->path());

        $serverSubdirHome = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/sctech-site/',
            'SCRIPT_NAME' => '/sctech-site/public/index.php',
        ];
        $requestSubdirHome = Request::fromGlobals($serverSubdirHome);
        self::assertSame('/sctech-site', $requestSubdirHome->basePath());
        self::assertSame('/', $requestSubdirHome->path());
    }

    public function testResponseRedirectRespectsBasePath(): void
    {
        Response::setBasePath('/sctech-site');
        try {
            $response = Response::redirect('/admin/login', 302);
            self::assertSame('/sctech-site/admin/login', $response->headerLine('Location'));
        } finally {
            Response::setBasePath('');
        }
    }
}

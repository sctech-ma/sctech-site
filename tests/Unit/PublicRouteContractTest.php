<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Core\Container;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Router;
use SCTech\Core\Session;
use SCTech\Middleware\SessionMiddleware;

final class PublicRouteContractTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $container = new Container();
        $container->instance(
            SessionMiddleware::class,
            new SessionMiddleware(new Session('sctech_route_test', ['path' => '/', 'samesite' => 'Lax']))
        );
        $this->router = new Router($container, 'https://www.sctech.ma');
        (require dirname(__DIR__, 2) . '/routes/web.php')($this->router, $container);
        $this->router->setNotFoundHandler(static fn (): Response => Response::text('missing', 404));
        $this->router->setMethodNotAllowedHandler(
            static fn (): Response => Response::text('method', 405)
        );
    }

    public function testCanonicalNamedRouteSetAndGeneration(): void
    {
        $expected = [
            'home' => '/',
            'solutions.index' => '/solutions',
            'solutions.show' => '/solutions/{slug}',
            'expertise' => '/expertise',
            'islamic-finance' => '/finance-islamique',
            'approach' => '/approche',
            'about' => '/a-propos',
            'insights.index' => '/insights',
            'insights.show' => '/insights/{slug}',
            'contact' => '/contact',
            'project' => '/demander-un-projet',
            'project.submit' => '/demander-un-projet',
        ];
        $actual = [];
        foreach ($this->router->routes() as $route) {
            if ($route->getName() !== null) {
                $actual[$route->getName()] = $route->uri();
            }
        }
        foreach ($expected as $name => $uri) {
            self::assertSame($uri, $actual[$name] ?? null, $name);
        }
        self::assertSame(
            'https://www.sctech.ma/solutions/data-reporting',
            $this->router->url('solutions.show', ['slug' => 'data-reporting'])
        );
        self::assertSame(
            'https://www.sctech.ma/demander-un-projet?solution=data-reporting',
            $this->router->url('project', [], ['solution' => 'data-reporting'])
        );
    }

    public function testConstraintsCanonicalSlashAndMethodHandling(): void
    {
        $invalidSlug = $this->router->dispatch(Request::create('GET', '/solutions/Bad_Slug'));
        self::assertSame(404, $invalidSlug->status());

        $slash = $this->router->dispatch(Request::create('GET', '/solutions/'));
        self::assertSame(308, $slash->status());
        self::assertSame('/solutions', $slash->headerLine('Location'));

        $method = $this->router->dispatch(Request::create('POST', '/solutions'));
        self::assertSame(405, $method->status());
        self::assertSame('GET, HEAD', $method->headerLine('Allow'));
    }

    public function testLegacyGetRedirectsArePermanentAndCanonical(): void
    {
        $redirects = [
            '/expertises' => '/expertise',
            '/expertises/data-ia' => '/solutions/data-reporting',
            '/methode' => '/approche',
            '/secteurs' => '/solutions',
            '/demander-un-devis' => '/demander-un-projet',
            '/services.php' => '/solutions',
        ];
        foreach ($redirects as $source => $destination) {
            $response = $this->router->dispatch(Request::create('GET', $source));
            self::assertSame(301, $response->status(), $source);
            self::assertSame($destination, $response->headerLine('Location'), $source);
        }
    }
}

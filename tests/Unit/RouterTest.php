<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Core\Container;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Router;
use SCTech\Middleware\MiddlewareInterface;
use SCTech\Middleware\RequestHandlerInterface;

final class RouterTest extends TestCase
{
    public function testItDispatchesConstrainedParametersAndGeneratesNamedUrls(): void
    {
        $router = new Router(new Container(), 'https://sctech.test');
        $router->group(['prefix' => '/insights', 'name' => 'insights.'], function (Router $router): void {
            $router->get('/{slug}', static function (Request $request, string $slug): Response {
                return Response::text($slug . '|' . $request->attribute('slug'));
            })->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('show');
        });

        $response = $router->dispatch(Request::create('GET', '/insights/data-securisee'));

        self::assertSame(200, $response->status());
        self::assertSame('data-securisee|data-securisee', $response->body());
        self::assertSame(
            'https://sctech.test/insights/data-securisee?source=home',
            $router->url('insights.show', ['slug' => 'data-securisee'], ['source' => 'home'])
        );
    }

    public function testHeadFallsBackToGetWithoutSendingABody(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): Response => Response::text('healthy'));

        $response = $router->dispatch(Request::create('HEAD', '/health'));

        self::assertSame(200, $response->status());
        self::assertSame('', $response->body());
        self::assertSame('7', $response->headerLine('Content-Length'));
    }

    public function testExplicitHeadRouteTakesPriorityOverGetFallback(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): Response => Response::text('get-response'));
        $router->map('HEAD', '/health', static fn (): Response => new Response('', 204));

        $response = $router->dispatch(Request::create('HEAD', '/health'));

        self::assertSame(204, $response->status());
        self::assertSame('', $response->body());
    }

    public function testItReturns405WithCompleteAllowHeader(): void
    {
        $router = new Router();
        $router->get('/contact', static fn (): string => 'form');
        $router->post('/contact', static fn (): string => 'submitted');

        $response = $router->dispatch(Request::create('DELETE', '/contact'));

        self::assertSame(405, $response->status());
        self::assertSame('GET, HEAD, POST', $response->headerLine('Allow'));
    }

    public function testItCanonicalizesTrailingSlashesAndPreservesQuery(): void
    {
        $router = new Router();
        $response = $router->dispatch(Request::create('GET', '/expertises/?source=legacy'));

        self::assertSame(308, $response->status());
        self::assertSame('/expertises?source=legacy', $response->headerLine('Location'));
    }

    public function testNotFoundEscapesTheRequestedPath(): void
    {
        $router = new Router();
        $response = $router->dispatch(Request::create('GET', '/<script>'));

        self::assertSame(404, $response->status());
        self::assertStringNotContainsString('<script>', $response->body());
        self::assertStringContainsString('&lt;script&gt;', $response->body());
    }

    public function testGlobalAndRouteMiddlewareExecuteInOrder(): void
    {
        $events = new \ArrayObject();
        $outer = new RecordingMiddleware('outer', $events);
        $inner = new RecordingMiddleware('inner', $events);
        $router = new Router();
        $router->use($outer);
        $router->get('/', static function () use ($events): string {
            $events[] = 'handler';

            return 'ok';
        })->middleware($inner);

        $router->dispatch(Request::create('GET', '/'));

        self::assertSame(
            ['outer:before', 'inner:before', 'handler', 'inner:after', 'outer:after'],
            $events->getArrayCopy()
        );
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Tests\Integration;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SCTech\Core\Request;
use SCTech\Core\Router;

final class AdminRouteSmokeTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testLoginRendersAndProtectedAdminRoutesRedirectWithoutAUser(): void
    {
        $_ENV['APP_KEY'] = str_repeat('test-key-', 8);
        $_SERVER['APP_KEY'] = $_ENV['APP_KEY'];
        $_ENV['APP_URL'] = 'http://localhost';
        $_SERVER['APP_URL'] = 'http://localhost';

        /** @var Router $router */
        $router = require dirname(__DIR__, 2) . '/bootstrap/app.php';

        $login = $router->dispatch(Request::create('GET', '/admin/login'));
        self::assertSame(200, $login->status());
        self::assertStringContainsString('Connexion à l’administration', $login->body());
        self::assertSame('noindex, nofollow', $login->headerLine('X-Robots-Tag'));

        foreach (['/admin', '/admin/pages', '/admin/preview/articles/1', '/admin/messages', '/admin/parametres'] as $path) {
            $response = $router->dispatch(Request::create('GET', $path));
            self::assertSame(302, $response->status(), $path);
            self::assertSame('/admin/login', $response->headerLine('Location'), $path);
        }
    }
}

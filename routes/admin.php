<?php

declare(strict_types=1);

use SCTech\Controllers\Admin\AuthController;
use SCTech\Controllers\Admin\ContentController;
use SCTech\Controllers\Admin\DashboardController;
use SCTech\Controllers\Admin\LeadsController;
use SCTech\Controllers\Admin\MediaController;
use SCTech\Controllers\Admin\SettingsController;
use SCTech\Core\Container;
use SCTech\Core\Router;
use SCTech\Middleware\NoCacheMiddleware;
use SCTech\Middleware\SessionMiddleware;

return static function (Router $router, Container $container): void {
    $router->group([
        'prefix' => '/admin',
        'name' => 'admin.',
        'middleware' => [$container->get(NoCacheMiddleware::class), $container->get(SessionMiddleware::class)],
    ], static function (Router $router): void {
        $router->get('/login', [AuthController::class, 'loginForm'])->name('login');
        $router->post('/login', [AuthController::class, 'login'])->name('login.submit');
        $router->post('/logout', [AuthController::class, 'logout'])->name('logout');
        $router->get('', [DashboardController::class, 'index'])->name('dashboard');

        foreach (['pages', 'expertises', 'secteurs', 'realisations', 'articles'] as $type) {
            $router->get('/' . $type, [ContentController::class, 'index'])->name($type . '.index');
            $router->get('/' . $type . '/nouveau', [ContentController::class, 'create'])->name($type . '.create');
            $router->post('/' . $type, [ContentController::class, 'save'])->name($type . '.store');
            $router->get('/' . $type . '/{id}/modifier', [ContentController::class, 'edit'])
                ->where('id', '[1-9][0-9]*')->name($type . '.edit');
            $router->post('/' . $type . '/{id}', [ContentController::class, 'save'])
                ->where('id', '[1-9][0-9]*')->name($type . '.update');
            $router->get('/preview/' . $type . '/{id}', [ContentController::class, 'preview'])
                ->where('id', '[1-9][0-9]*')->name($type . '.preview');
        }

        $router->get('/medias', [MediaController::class, 'index'])->name('media.index');
        $router->post('/medias', [MediaController::class, 'upload'])->name('media.upload');
        $router->get('/messages', [LeadsController::class, 'index'])->name('messages.index');
        $router->get('/messages/{id}', [LeadsController::class, 'show'])
            ->where('id', '[1-9][0-9]*')->name('messages.show');
        $router->post('/messages/{id}', [LeadsController::class, 'update'])
            ->where('id', '[1-9][0-9]*')->name('messages.update');
        $router->get('/devis', [LeadsController::class, 'index'])->name('quotes.index');
        $router->get('/devis/{id}', [LeadsController::class, 'show'])
            ->where('id', '[1-9][0-9]*')->name('quotes.show');
        $router->post('/devis/{id}', [LeadsController::class, 'update'])
            ->where('id', '[1-9][0-9]*')->name('quotes.update');
        $router->get('/parametres', [SettingsController::class, 'index'])->name('settings.index');
        $router->post('/parametres', [SettingsController::class, 'save'])->name('settings.save');
    });
};

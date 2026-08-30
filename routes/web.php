<?php

declare(strict_types=1);

use SCTech\Controllers\PublicSite\FormController;
use SCTech\Controllers\PublicSite\SiteController;
use SCTech\Core\Container;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Router;
use SCTech\Core\View;
use SCTech\Middleware\SessionMiddleware;

return static function (Router $router, Container $container): void {
    $session = $container->get(SessionMiddleware::class);

    $router->get('/', [SiteController::class, 'home'])->name('home');
    $router->get('/solutions', [SiteController::class, 'solutions'])->name('solutions.index');
    $router->get('/solutions/{slug}', [SiteController::class, 'solution'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('solutions.show');
    $router->get('/expertise', [SiteController::class, 'expertise'])->name('expertise');
    $router->get('/finance-islamique', [SiteController::class, 'islamicFinance'])->name('islamic-finance');
    $router->get('/approche', [SiteController::class, 'approach'])->name('approach');
    $router->get('/a-propos', [SiteController::class, 'about'])->name('about');
    $router->get('/insights', [SiteController::class, 'insights'])->name('insights.index');
    $router->get('/insights/{slug}', [SiteController::class, 'article'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('insights.show');
    $router->get('/contact', [SiteController::class, 'contact'])->middleware($session)->name('contact');
    $router->post('/contact', [FormController::class, 'contact'])->middleware($session)->name('contact.submit');
    $router->get('/demander-un-projet', [SiteController::class, 'project'])->middleware($session)->name('project');
    $router->post('/demander-un-projet', [FormController::class, 'quote'])->middleware($session)->name('project.submit');

    $router->get('/realisations', [SiteController::class, 'realizations'])->name('realizations.index');
    $router->get('/realisations/{slug}', [SiteController::class, 'realization'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('realizations.show');
    $router->get('/mentions-legales', [SiteController::class, 'legal'])->name('legal');
    $router->get('/politique-confidentialite', [SiteController::class, 'privacy'])->name('privacy');
    $router->get('/politique-cookies', [SiteController::class, 'cookies'])->name('cookies');
    $router->get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
    $router->get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
    $router->get('/404', [SiteController::class, 'notFound'])->name('error.404');

    $redirects = [
        '/index.php' => '/',
        '/services.php' => '/solutions',
        '/about.php' => '/a-propos',
        '/contact.php' => '/contact',
        '/expertises' => '/expertise',
        '/expertises/data-ia' => '/solutions/data-reporting',
        '/expertises/developpement-securise' => '/expertise',
        '/expertises/cloud-devsecops' => '/expertise',
        '/expertises/cybersecurite-conformite' => '/expertise',
        '/expertises/transformation-digitale' => '/approche',
        '/expertises/nearshoring' => '/a-propos',
        '/secteurs' => '/solutions',
        '/methode' => '/approche',
        '/demander-un-devis' => '/demander-un-projet',
    ];
    foreach ($redirects as $legacy => $destination) {
        $router->get($legacy, static fn (): Response => Response::redirect($destination, 301));
    }
    $router->post('/demander-un-devis', [FormController::class, 'quote'])
        ->middleware($session)
        ->name('legacy.quote.submit');
    $router->get('/process-contact.php', static fn (): Response => Response::redirect('/contact', 301))
        ->name('legacy.contact.get');
    $router->post('/process-contact.php', [FormController::class, 'legacy'])
        ->name('legacy.contact.post');

    $router->setNotFoundHandler([SiteController::class, 'notFound']);
    $router->setMethodNotAllowedHandler(static function (Request $request, array $allowed) use ($container): Response {
        $view = $container->get(View::class);
        return $view->response('errors/405', [
            'title' => 'Méthode non autorisée — SCTECH',
            'description' => 'Cette méthode HTTP n’est pas disponible pour cette adresse.',
            'robots' => 'noindex,nofollow',
            'currentPath' => $request->path(),
            'canonical' => null,
            'pageClass' => 'page-error',
        ], 'layouts/public', 405, ['Allow' => implode(', ', $allowed)]);
    });
};

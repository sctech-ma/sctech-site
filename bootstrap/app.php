<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use SCTech\Core\Config;
use SCTech\Core\Container;
use SCTech\Core\Database;
use SCTech\Core\Logger;
use SCTech\Core\Router;
use SCTech\Core\Session;
use SCTech\Core\View;
use SCTech\Middleware\ErrorHandlerMiddleware;
use SCTech\Middleware\NoCacheMiddleware;
use SCTech\Middleware\RequestIdMiddleware;
use SCTech\Middleware\SecurityHeadersMiddleware;
use SCTech\Middleware\SessionMiddleware;
use SCTech\Middleware\TrustedProxyMiddleware;
use SCTech\Repositories\ContactMessageRepository;
use SCTech\Repositories\ContactMessageStore;
use SCTech\Repositories\QuoteRequestRepository;
use SCTech\Repositories\QuoteRequestStore;
use SCTech\Services\AdminAuthService;
use SCTech\Services\Clock;
use SCTech\Services\ContactWorkflow;
use SCTech\Services\CoreSessionStore;
use SCTech\Services\FormTokenService;
use SCTech\Services\LeadNotificationService;
use SCTech\Services\MailTransport;
use SCTech\Services\MediaUploadService;
use SCTech\Services\PersistentRateLimiter;
use SCTech\Services\PHPMailerTransport;
use SCTech\Services\QuoteWorkflow;
use SCTech\Services\RateLimiter as FormRateLimiter;
use SCTech\Services\SessionStore;
use SCTech\Services\SubmissionHasher;
use SCTech\Services\SystemClock;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

Dotenv::createImmutable($root)->safeLoad();

$config = Config::fromDirectory($root . '/config');
date_default_timezone_set($config->string('app.timezone', 'UTC'));

$debug = $config->bool('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$secret = $config->string('app.key');
if (strlen($secret) < 32) {
    throw new RuntimeException('APP_KEY must contain at least 32 characters.');
}

$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Container::class, $container);

$database = new Database($config);
$container->instance(Database::class, $database);
$container->singleton(\PDO::class, static fn (Container $services): \PDO => $services->get(Database::class)->pdo());

$appUrl = $config->string('app.url');
$secureCookies = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https';
$session = new Session($config->string('security.session_name', 'sctech_session'), [
    'path' => '/',
    'secure' => $secureCookies,
    'samesite' => 'Lax',
]);
$container->instance(Session::class, $session);
$container->instance(SessionMiddleware::class, new SessionMiddleware($session));
$container->singleton(SessionStore::class, static fn (Container $services): SessionStore => new CoreSessionStore(
    $services->get(Session::class)
));

$logFile = $root . '/storage/logs/app.log';
$logger = new Logger($logFile, $debug ? 'debug' : 'info', ['application' => 'sctech']);
$container->instance(Logger::class, $logger);

$manifest = [];
$manifestFile = $root . '/public/assets/build/manifest.json';
if (is_file($manifestFile)) {
    $decodedManifest = json_decode((string) file_get_contents($manifestFile), true);
    if (is_array($decodedManifest)) {
        $manifest = array_filter($decodedManifest, 'is_string');
    }
}
$view = new View($config->string('app.view_path', $root . '/app/Views'));
$view->shareMany([
    'assetManifest' => $manifest,
    'asset' => static function (string $path) use ($manifest): string {
        $resolved = $manifest[$path] ?? $path;
        return asset($resolved);
    },
]);
$container->instance(View::class, $view);

$container->singleton(Clock::class, SystemClock::class);
$container->singleton(SubmissionHasher::class, static fn (): SubmissionHasher => new SubmissionHasher($secret));
$container->singleton(FormTokenService::class, static fn (Container $services): FormTokenService => new FormTokenService(
    $secret,
    $services->get(Clock::class)
));
$container->singleton(FormRateLimiter::class, static fn (Container $services): FormRateLimiter => new PersistentRateLimiter(
    $services->get(SCTech\Repositories\RateLimitRepository::class),
    $services->get(Clock::class),
    $secret,
    $config->int('security.form_rate_15_min', 5),
    $config->int('security.form_rate_day', 20)
));

$container->singleton(ContactMessageStore::class, ContactMessageRepository::class);
$container->singleton(QuoteRequestStore::class, QuoteRequestRepository::class);
$container->singleton(MailTransport::class, static fn (): MailTransport => new PHPMailerTransport(
    (array) $config->get('mail', [])
));
$container->singleton(LeadNotificationService::class);

$container->singleton(ContactWorkflow::class, static fn (Container $services): ContactWorkflow => new ContactWorkflow(
    $services->get(SCTech\Validation\ContactValidator::class),
    $services->get(SCTech\Services\FormSecurityGate::class),
    $services->get(SubmissionHasher::class),
    $services->get(ContactMessageStore::class),
    $services->get(LeadNotificationService::class),
    $config->bool('mail.required', true)
));
$container->singleton(QuoteWorkflow::class, static fn (Container $services): QuoteWorkflow => new QuoteWorkflow(
    $services->get(SCTech\Validation\QuoteValidator::class),
    $services->get(SCTech\Services\FormSecurityGate::class),
    $services->get(SubmissionHasher::class),
    $services->get(QuoteRequestStore::class),
    $services->get(LeadNotificationService::class),
    $config->bool('mail.required', true)
));
$container->singleton(MediaUploadService::class, static fn (Container $services): MediaUploadService => new MediaUploadService(
    $services->get(SCTech\Repositories\MediaRepository::class),
    $services->get(SCTech\Repositories\AdminAuditRepository::class),
    $root . '/public/uploads',
    (array) $config->get('security.upload_mimes', []),
    $config->int('security.upload_max_bytes', 5 * 1024 * 1024),
    $config->int('security.upload_max_pixels', 20_000_000)
));
$container->singleton(AdminAuthService::class, static fn (Container $services): AdminAuthService => new AdminAuthService(
    $services->get(SCTech\Repositories\UserRepository::class),
    $services->get(SessionStore::class),
    $services->get(Clock::class),
    $services->get(SubmissionHasher::class),
    $services->get(SCTech\Repositories\AdminAuditRepository::class),
    $config->int('security.idle_seconds', 1800),
    $config->int('security.absolute_seconds', 28800)
));

$trustedProxies = $config->get('security.trusted_proxies', []);
$trustedProxies = is_array($trustedProxies) ? array_values(array_filter($trustedProxies, 'is_string')) : [];
$router = new Router($container, $appUrl);
$router->use(
    new RequestIdMiddleware(),
    new TrustedProxyMiddleware($trustedProxies),
    new SecurityHeadersMiddleware(
        $config->bool('security.hsts_enabled', false),
        $trustedProxies,
        $config->bool('security.upgrade_insecure_requests', false)
    ),
    new ErrorHandlerMiddleware($logger, $debug)
);
$container->instance(Router::class, $router);
$container->instance(NoCacheMiddleware::class, new NoCacheMiddleware());

(require $root . '/routes/web.php')($router, $container);
(require $root . '/routes/admin.php')($router, $container);

// Public templates resolve links from stable route names instead of duplicating paths.
$view->share('routeUrl', static fn (
    string $name,
    array $parameters = [],
    array $query = []
): string => $router->url($name, $parameters, $query));

return $router;

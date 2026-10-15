<?php

declare(strict_types=1);

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Router;
use SCTech\Helpers\Url;

if (!defined('SCTECH_PUBLIC_PATH')) {
    define('SCTECH_PUBLIC_PATH', __DIR__);
}

// Locate the application bootstrap file in both local and shared hosting layouts:
// 1. Local / standard docroot: public/ -> parent directory (C:\laragon\www\sctech-site)
// 2. Production shared hosting: public_html/ -> sibling sctech-site directory (/home/<cpanel-user>/sctech-site)
// 3. Optional environment variable override (APP_ROOT or SCTECH_APP_ROOT)
$bootstrapCandidates = [];

$envAppRoot = getenv('APP_ROOT') ?: getenv('SCTECH_APP_ROOT') ?: ($_SERVER['APP_ROOT'] ?? $_ENV['APP_ROOT'] ?? null);
if (is_string($envAppRoot) && $envAppRoot !== '') {
    $bootstrapCandidates[] = rtrim($envAppRoot, '/\\') . '/bootstrap/app.php';
}

$bootstrapCandidates[] = dirname(__DIR__) . '/bootstrap/app.php';
$bootstrapCandidates[] = dirname(__DIR__) . '/sctech-site/bootstrap/app.php';
$bootstrapCandidates[] = dirname(__DIR__) . '/repositories/sctech-site/bootstrap/app.php';

$bootstrapFile = null;
foreach ($bootstrapCandidates as $candidate) {
    if (is_file($candidate)) {
        $bootstrapFile = $candidate;
        break;
    }
}

if ($bootstrapFile === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Application bootstrap file could not be located.';
    exit(1);
}

/** @var Router $router */
$router = require $bootstrapFile;
$request = Request::fromGlobals();
if ($request->basePath() !== '') {
    $router->setBaseUrl($request->basePath());
    Response::setBasePath($request->basePath());
    Url::setBasePath($request->basePath());
}
$router->dispatch($request)->send();


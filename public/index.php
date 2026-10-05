<?php

declare(strict_types=1);

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Router;
use SCTech\Helpers\Url;

/** @var Router $router */
$router = require dirname(__DIR__) . '/bootstrap/app.php';
$request = Request::fromGlobals();
if ($request->basePath() !== '') {
    $router->setBaseUrl($request->basePath());
    Response::setBasePath($request->basePath());
    Url::setBasePath($request->basePath());
}
$router->dispatch($request)->send();

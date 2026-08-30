<?php

declare(strict_types=1);

use SCTech\Core\Request;
use SCTech\Core\Router;

/** @var Router $router */
$router = require dirname(__DIR__) . '/bootstrap/app.php';
$router->dispatch(Request::fromGlobals())->send();

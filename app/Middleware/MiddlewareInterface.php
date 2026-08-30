<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}

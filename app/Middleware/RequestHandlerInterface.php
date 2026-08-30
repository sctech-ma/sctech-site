<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}

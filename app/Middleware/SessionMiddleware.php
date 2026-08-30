<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Session $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->session->start();

        try {
            return $next->handle($request->withAttribute('session', $this->session));
        } finally {
            $this->session->commit();
        }
    }
}

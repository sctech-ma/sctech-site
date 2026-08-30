<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;

final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly string $dashboardPath = '/admin'
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->session->start();
        $user = $this->session->get('auth.user');
        if (is_array($user) && isset($user['id'])) {
            return Response::redirect($this->dashboardPath, 302)->withHeader('Cache-Control', 'no-store');
        }

        return $next->handle($request);
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;

final class AuthMiddleware implements ParameterizedMiddlewareInterface
{
    /** @param list<string> $roles */
    public function __construct(
        private readonly Session $session,
        private readonly string $loginPath = '/admin/login',
        private readonly array $roles = [],
        private readonly int $idleTimeout = 1800,
        private readonly int $absoluteTimeout = 28800
    ) {
    }

    /** @param list<string> $parameters */
    public function withParameters(array $parameters): MiddlewareInterface
    {
        return new self(
            $this->session,
            $this->loginPath,
            $parameters,
            $this->idleTimeout,
            $this->absoluteTimeout
        );
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->session->start();
        $user = $this->session->get('auth.user');
        $now = time();

        if (!is_array($user) || !isset($user['id'], $user['role'])) {
            return $this->unauthenticated($request);
        }

        $loggedInAt = (int) $this->session->get('auth.started_at', $now);
        $lastActivity = (int) $this->session->get('auth.last_seen_at', $now);
        if (($now - $lastActivity) > $this->idleTimeout || ($now - $loggedInAt) > $this->absoluteTimeout) {
            $this->session->invalidate();
            $this->session->flash('error', 'Votre session a expiré. Veuillez vous reconnecter.');

            return $this->unauthenticated($request);
        }

        if ($this->roles !== [] && !in_array((string) $user['role'], $this->roles, true)) {
            return ($request->expectsJson()
                ? Response::json(['error' => 'Accès refusé.'], 403)
                : Response::html(
                    '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Accès refusé</title>'
                    . '<main><h1>Accès refusé</h1><p>Votre rôle ne permet pas cette action.</p></main>',
                    403
                ))->withHeader('Cache-Control', 'no-store');
        }

        $this->session->put('auth.started_at', $loggedInAt);
        $this->session->put('auth.last_seen_at', $now);
        $response = $next->handle($request->withAttribute('user', $user));

        return $response
            ->withHeader('Cache-Control', 'no-store, private')
            ->withHeader('Pragma', 'no-cache');
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json(['error' => 'Authentification requise.'], 401)
                ->withHeader('Cache-Control', 'no-store');
        }

        if ($request->isMethod('GET', 'HEAD')) {
            $query = $request->queryString();
            $this->session->put('auth.intended', $request->path() . ($query !== '' ? '?' . $query : ''));
        }

        return Response::redirect($this->loginPath, $request->isMethod('GET', 'HEAD') ? 302 : 303)
            ->withHeader('Cache-Control', 'no-store');
    }
}

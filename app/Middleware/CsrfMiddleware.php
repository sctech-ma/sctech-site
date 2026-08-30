<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Csrf;
use SCTech\Core\Request;
use SCTech\Core\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    /** @param list<string> $except */
    public function __construct(
        private readonly Csrf $csrf,
        private readonly array $except = []
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($request->isMethod('GET', 'HEAD', 'OPTIONS') || $this->excluded($request->path())) {
            return $next->handle($request);
        }

        if (!$this->csrf->verify($this->csrf->tokenFromRequest($request))) {
            $response = $request->expectsJson()
                ? Response::json(['error' => 'Jeton de sécurité invalide ou expiré.'], 419)
                : Response::html(
                    '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Session expirée</title>'
                    . '<main><h1>Votre session a expiré</h1>'
                    . '<p>Rechargez la page, puis renvoyez le formulaire.</p></main>',
                    419
                );

            return $response->withHeader('Cache-Control', 'no-store');
        }

        return $next->handle($request);
    }

    private function excluded(string $path): bool
    {
        foreach ($this->except as $pattern) {
            if ($pattern === $path) {
                return true;
            }
            if (str_ends_with($pattern, '*') && str_starts_with($path, substr($pattern, 0, -1))) {
                return true;
            }
        }

        return false;
    }
}

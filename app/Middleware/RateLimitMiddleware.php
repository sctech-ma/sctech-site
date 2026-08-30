<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use Closure;
use SCTech\Core\RateLimiter;
use SCTech\Core\Request;
use SCTech\Core\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private readonly ?Closure $keyResolver;

    /** @param list<string> $trustedProxies */
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly int $limit = 60,
        private readonly int $decaySeconds = 60,
        private readonly string $prefix = 'http',
        private readonly array $trustedProxies = [],
        ?callable $keyResolver = null
    ) {
        $this->keyResolver = $keyResolver !== null ? Closure::fromCallable($keyResolver) : null;
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $identity = $this->keyResolver !== null
            ? (string) ($this->keyResolver)($request)
            : $request->clientIp($this->trustedProxies);
        $routeName = $request->attribute('route_name');
        $route = is_string($routeName) && $routeName !== '' ? $routeName : $request->path();
        $result = $this->limiter->consume(
            $this->prefix . '|' . $route . '|' . $identity,
            $this->limit,
            $this->decaySeconds
        );

        if (!$result->allowed) {
            $response = $request->expectsJson()
                ? Response::json(['error' => 'Trop de requêtes. Réessayez plus tard.'], 429)
                : Response::html(
                    '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Trop de requêtes</title>'
                    . '<main><h1>Trop de requêtes</h1><p>Veuillez patienter avant de réessayer.</p></main>',
                    429
                );
            $response = $response->withHeader('Retry-After', (string) max(1, $result->retryAfter));
        } else {
            $response = $next->handle($request);
        }

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $result->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining)
            ->withHeader('X-RateLimit-Reset', (string) $result->resetsAt);
    }
}

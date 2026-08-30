<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use JsonSerializable;
use SCTech\Helpers\Escaper;
use SCTech\Middleware\CallableRequestHandler;
use SCTech\Middleware\MiddlewareInterface;
use SCTech\Middleware\MiddlewarePipeline;
use SCTech\Middleware\ParameterizedMiddlewareInterface;
use Stringable;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var list<MiddlewareInterface|callable|string> */
    private array $globalMiddleware = [];

    /** @var array<string, MiddlewareInterface|callable|string> */
    private array $middlewareAliases = [];

    /**
     * @var list<array{
     *     prefix: string,
     *     name: string,
     *     middleware: list<MiddlewareInterface|callable|string>,
     *     constraints: array<string, string>
     * }>
     */
    private array $groups = [];

    private mixed $notFoundHandler = null;
    private mixed $methodNotAllowedHandler = null;

    public function __construct(
        private readonly Container $container = new Container(),
        private string $baseUrl = ''
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function get(string $uri, mixed $handler): Route
    {
        return $this->add(['GET'], $uri, $handler);
    }

    public function post(string $uri, mixed $handler): Route
    {
        return $this->add(['POST'], $uri, $handler);
    }

    public function put(string $uri, mixed $handler): Route
    {
        return $this->add(['PUT'], $uri, $handler);
    }

    public function patch(string $uri, mixed $handler): Route
    {
        return $this->add(['PATCH'], $uri, $handler);
    }

    public function delete(string $uri, mixed $handler): Route
    {
        return $this->add(['DELETE'], $uri, $handler);
    }

    /** @param list<string>|string $methods */
    public function map(array|string $methods, string $uri, mixed $handler): Route
    {
        return $this->add((array) $methods, $uri, $handler);
    }

    public function any(string $uri, mixed $handler): Route
    {
        return $this->add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $handler);
    }

    /** @param list<string> $methods */
    public function add(array $methods, string $uri, mixed $handler): Route
    {
        $group = $this->currentGroup();
        $uri = $this->joinPaths($group['prefix'], $uri);
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $route = new Route(
            $methods,
            $uri,
            $handler,
            $group['constraints'],
            $group['middleware'],
            $group['name']
        );
        $this->routes[] = $route;

        return $route;
    }

    /**
     * @param string|array{
     *     prefix?: string,
     *     name?: string,
     *     middleware?: MiddlewareInterface|callable|string|list<MiddlewareInterface|callable|string>,
     *     where?: array<string, string>
     * } $attributes
     */
    public function group(string|array $attributes, callable $routes): void
    {
        if (is_string($attributes)) {
            $attributes = ['prefix' => $attributes];
        }

        $parent = $this->currentGroup();
        $middleware = $this->normalizeMiddleware($attributes['middleware'] ?? []);
        $constraints = $attributes['where'] ?? [];

        $this->groups[] = [
            'prefix' => $this->joinPaths($parent['prefix'], (string) ($attributes['prefix'] ?? '')),
            'name' => $parent['name'] . (string) ($attributes['name'] ?? ''),
            'middleware' => [...$parent['middleware'], ...$middleware],
            'constraints' => [...$parent['constraints'], ...$constraints],
        ];

        try {
            $routes($this);
        } finally {
            array_pop($this->groups);
        }
    }

    public function use(MiddlewareInterface|callable|string ...$middleware): self
    {
        array_push($this->globalMiddleware, ...$middleware);

        return $this;
    }

    public function aliasMiddleware(string $name, MiddlewareInterface|callable|string $middleware): self
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw new InvalidArgumentException('Invalid middleware alias.');
        }

        $this->middlewareAliases[$name] = $middleware;

        return $this;
    }

    public function setNotFoundHandler(mixed $handler): self
    {
        $this->notFoundHandler = $handler;

        return $this;
    }

    public function setMethodNotAllowedHandler(mixed $handler): self
    {
        $this->methodNotAllowedHandler = $handler;

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        if ($request->path() !== '/' && str_ends_with($request->path(), '/')) {
            $location = rtrim($request->path(), '/');
            $query = $request->queryString();
            if ($query !== '') {
                $location .= '?' . $query;
            }

            $response = $this->runPipeline(
                $request,
                $this->globalMiddleware,
                fn (): Response => Response::redirect($location, 308)
            );

            return $request->isMethod('HEAD') ? $response->forHead() : $response;
        }

        [$route, $parameters, $allowed] = $this->findRoute($request);

        if ($route instanceof Route) {
            $request = $request->withAttributes([
                'route' => $route,
                'route_name' => $route->getName(),
                'route_params' => $parameters,
            ] + $parameters);

            $middleware = [...$this->globalMiddleware, ...$route->middlewareStack()];
            $response = $this->runPipeline(
                $request,
                $middleware,
                fn (Request $request): Response => $this->invokeRoute($route, $request, $parameters)
            );
        } elseif ($allowed !== []) {
            $response = $this->runPipeline(
                $request,
                $this->globalMiddleware,
                fn (Request $request): Response => $this->methodNotAllowed($request, $allowed)
            );
        } else {
            $response = $this->runPipeline(
                $request,
                $this->globalMiddleware,
                fn (Request $request): Response => $this->notFound($request)
            );
        }

        return $request->isMethod('HEAD') ? $response->forHead() : $response;
    }

    /**
     * @param array<string, scalar|Stringable> $parameters
     * @param array<string, scalar|list<scalar>|null> $query
     */
    public function url(string $name, array $parameters = [], array $query = []): string
    {
        $found = null;
        foreach ($this->routes as $route) {
            if ($route->getName() !== $name) {
                continue;
            }
            if ($found !== null) {
                throw new InvalidArgumentException(sprintf('Duplicate route name "%s".', $name));
            }
            $found = $route;
        }

        if (!$found instanceof Route) {
            throw new InvalidArgumentException(sprintf('Unknown route name "%s".', $name));
        }

        $url = $this->baseUrl . $found->buildPath($parameters);
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    /** @return array{0: ?Route, 1: array<string, string>, 2: list<string>} */
    private function findRoute(Request $request): array
    {
        $allowed = [];
        $requestMethod = $request->method();
        $getFallback = null;

        foreach ($this->routes as $route) {
            $parameters = $route->match($request->path());
            if ($parameters === null) {
                continue;
            }

            $methods = $route->methods();
            array_push($allowed, ...$methods);
            if (in_array('GET', $methods, true)) {
                $allowed[] = 'HEAD';
            }

            if (in_array($requestMethod, $methods, true)) {
                return [$route, $parameters, []];
            }

            if ($requestMethod === 'HEAD' && in_array('GET', $methods, true) && $getFallback === null) {
                $getFallback = [$route, $parameters, []];
            }
        }

        if (is_array($getFallback)) {
            return $getFallback;
        }

        $allowed = array_values(array_unique($allowed));
        sort($allowed, SORT_STRING);

        return [null, [], $allowed];
    }

    /** @param array<string, string> $parameters */
    private function invokeRoute(Route $route, Request $request, array $parameters): Response
    {
        $result = $this->container->call($route->handler(), [
            'request' => $request,
            ...$parameters,
        ]);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof JsonSerializable) {
            return Response::json($result);
        }

        if (is_string($result) || $result instanceof Stringable) {
            return Response::html((string) $result);
        }

        if ($result === null) {
            return new Response('', 204);
        }

        throw new InvalidArgumentException('Route handlers must return Response, array, string, Stringable, or null.');
    }

    /** @param list<string> $allowed */
    private function methodNotAllowed(Request $request, array $allowed): Response
    {
        if ($this->methodNotAllowedHandler !== null) {
            $result = $this->container->call($this->methodNotAllowedHandler, [
                'request' => $request,
                'allowed' => $allowed,
            ]);

            return $result instanceof Response
                ? $result->withHeader('Allow', implode(', ', $allowed))
                : Response::text('', 405, ['Allow' => implode(', ', $allowed)]);
        }

        if ($request->expectsJson()) {
            return Response::json(['error' => 'Méthode non autorisée.'], 405, ['Allow' => implode(', ', $allowed)]);
        }

        return Response::html(
            '<!doctype html><html lang="fr"><meta charset="utf-8"><title>405 — Méthode non autorisée</title>'
            . '<main><h1>Méthode non autorisée</h1><p>Cette action n’est pas disponible pour cette adresse.</p></main>',
            405,
            ['Allow' => implode(', ', $allowed)]
        );
    }

    private function notFound(Request $request): Response
    {
        if ($this->notFoundHandler !== null) {
            $result = $this->container->call($this->notFoundHandler, ['request' => $request]);

            return $result instanceof Response ? $result : Response::text('', 404);
        }

        if ($request->expectsJson()) {
            return Response::json(['error' => 'Ressource introuvable.'], 404);
        }

        return Response::html(
            '<!doctype html><html lang="fr"><meta charset="utf-8"><title>404 — Page introuvable</title>'
            . '<main><h1>Page introuvable</h1><p>Aucune page ne correspond à <code>'
            . Escaper::html($request->path())
            . '</code>.</p><p><a href="/">Revenir à l’accueil</a></p></main>',
            404
        );
    }

    /**
     * @param list<MiddlewareInterface|callable|string> $middleware
     * @param callable(Request): Response $destination
     */
    private function runPipeline(Request $request, array $middleware, callable $destination): Response
    {
        $resolved = array_map(fn (mixed $item): mixed => $this->resolveMiddleware($item), $middleware);
        $pipeline = new MiddlewarePipeline($this->container);

        return $pipeline->process($request, $resolved, new CallableRequestHandler($destination));
    }

    private function resolveMiddleware(mixed $middleware): mixed
    {
        if (!is_string($middleware) || class_exists($middleware)) {
            return $middleware;
        }

        [$alias, $parameterString] = array_pad(explode(':', $middleware, 2), 2, '');
        if (!array_key_exists($alias, $this->middlewareAliases)) {
            throw new InvalidArgumentException(sprintf('Unknown middleware alias "%s".', $alias));
        }

        $resolved = $this->middlewareAliases[$alias];
        if (is_string($resolved) && class_exists($resolved)) {
            $resolved = $this->container->get($resolved);
        }

        if ($parameterString !== '') {
            if (!$resolved instanceof ParameterizedMiddlewareInterface) {
                throw new InvalidArgumentException(sprintf('Middleware "%s" does not accept parameters.', $alias));
            }
            $resolved = $resolved->withParameters(array_values(array_filter(
                explode(',', $parameterString),
                static fn (string $value): bool => $value !== ''
            )));
        }

        return $resolved;
    }

    /**
     * @return array{
     *     prefix: string,
     *     name: string,
     *     middleware: list<MiddlewareInterface|callable|string>,
     *     constraints: array<string, string>
     * }
     */
    private function currentGroup(): array
    {
        return $this->groups[array_key_last($this->groups)] ?? [
            'prefix' => '',
            'name' => '',
            'middleware' => [],
            'constraints' => [],
        ];
    }

    private function joinPaths(string $prefix, string $uri): string
    {
        $prefix = trim($prefix, '/');
        $uri = trim($uri, '/');

        return $prefix === '' && $uri === ''
            ? '/'
            : '/' . implode('/', array_filter(
                [$prefix, $uri],
                static fn (string $value): bool => $value !== ''
            ));
    }

    /**
     * @param MiddlewareInterface|callable|string|list<MiddlewareInterface|callable|string> $middleware
     * @return list<MiddlewareInterface|callable|string>
     */
    private function normalizeMiddleware(mixed $middleware): array
    {
        if (!is_array($middleware) || is_callable($middleware)) {
            return [$middleware];
        }

        $normalized = [];
        foreach ($middleware as $item) {
            if ($item instanceof MiddlewareInterface || is_string($item) || is_callable($item)) {
                $normalized[] = $item;
                continue;
            }

            throw new InvalidArgumentException('Invalid route middleware definition.');
        }

        return $normalized;
    }
}

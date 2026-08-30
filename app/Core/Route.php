<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use SCTech\Middleware\MiddlewareInterface;

final class Route
{
    /** @var list<string> */
    private array $methods;

    /** @var array<string, string> */
    private array $constraints = [];

    /** @var list<MiddlewareInterface|callable|string> */
    private array $middleware = [];

    private ?string $routeName = null;
    private ?string $compiledPattern = null;

    /**
     * @param list<string> $methods
     * @param callable|array{0: object|string, 1: string}|string $handler
     * @param array<string, string> $constraints
     * @param list<MiddlewareInterface|callable|string> $middleware
     */
    public function __construct(
        array $methods,
        private readonly string $uri,
        private readonly mixed $handler,
        array $constraints = [],
        array $middleware = [],
        private readonly string $namePrefix = ''
    ) {
        if ($uri === '' || !str_starts_with($uri, '/')) {
            throw new InvalidArgumentException('Route URI must begin with a slash.');
        }

        $this->methods = array_values(array_unique(array_map('strtoupper', $methods)));
        if ($this->methods === []) {
            throw new InvalidArgumentException('A route must accept at least one HTTP method.');
        }

        foreach ($constraints as $parameter => $pattern) {
            $this->where($parameter, $pattern);
        }
        $this->middleware = $middleware;
    }

    /** @return list<string> */
    public function methods(): array
    {
        return $this->methods;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function name(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Route name cannot be empty.');
        }

        $this->routeName = $this->namePrefix . $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->routeName;
    }

    public function where(string $parameter, string $pattern): self
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $parameter) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid route parameter name "%s".', $parameter));
        }

        $pattern = trim($pattern);
        if ($pattern === '' || @preg_match('~^(?:' . $pattern . ')$~u', '') === false) {
            throw new InvalidArgumentException(sprintf('Invalid constraint for route parameter "%s".', $parameter));
        }

        $this->constraints[$parameter] = $pattern;
        $this->compiledPattern = null;

        return $this;
    }

    /** @param array<string, string> $constraints */
    public function whereMany(array $constraints): self
    {
        foreach ($constraints as $parameter => $pattern) {
            $this->where($parameter, $pattern);
        }

        return $this;
    }

    public function middleware(MiddlewareInterface|callable|string ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);

        return $this;
    }

    /** @return list<MiddlewareInterface|callable|string> */
    public function middlewareStack(): array
    {
        return $this->middleware;
    }

    /** @return array<string, string>|null */
    public function match(string $path): ?array
    {
        $matched = preg_match($this->compile(), $path, $values);
        if ($matched !== 1) {
            return null;
        }

        $parameters = [];
        foreach ($values as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $decoded = rawurldecode((string) $value);
            if (str_contains($decoded, "\0") || preg_match('//u', $decoded) !== 1) {
                return null;
            }
            $parameters[$name] = $decoded;
        }

        return $parameters;
    }

    /**
     * @param array<string, scalar|\Stringable> $parameters
     */
    public function buildPath(array $parameters = []): string
    {
        $used = [];
        $path = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)(?::([^{}]+))?\}/',
            function (array $matches) use ($parameters, &$used): string {
                $name = $matches[1];
                if (!array_key_exists($name, $parameters)) {
                    throw new InvalidArgumentException(sprintf(
                        'Missing parameter "%s" for route "%s".',
                        $name,
                        $this->uri
                    ));
                }

                $value = (string) $parameters[$name];
                $constraint = $this->constraints[$name] ?? ($matches[2] ?? '[^/]+');
                if (@preg_match('~^(?:' . $constraint . ')$~uD', $value) !== 1) {
                    throw new InvalidArgumentException(sprintf(
                        'Parameter "%s" does not satisfy its route constraint.',
                        $name
                    ));
                }

                $used[$name] = true;

                return rawurlencode($value);
            },
            $this->uri
        );

        if (!is_string($path)) {
            throw new InvalidArgumentException('Unable to generate route URL.');
        }

        $unknown = array_diff_key($parameters, $used);
        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unknown route parameter(s): ' . implode(', ', array_keys($unknown)) . '.'
            );
        }

        return $path;
    }

    private function compile(): string
    {
        if ($this->compiledPattern !== null) {
            return $this->compiledPattern;
        }

        $offset = 0;
        $pattern = '';
        $names = [];
        preg_match_all(
            '/\{([A-Za-z_][A-Za-z0-9_]*)(?::([^{}]+))?\}/',
            $this->uri,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $placeholder = $match[0][0];
            $position = $match[0][1];
            $name = $match[1][0];
            if (isset($names[$name])) {
                throw new InvalidArgumentException(sprintf('Duplicate route parameter "%s".', $name));
            }
            $names[$name] = true;

            $pattern .= preg_quote(substr($this->uri, $offset, $position - $offset), '~');
            $inlineConstraint = isset($match[2]) ? $match[2][0] : null;
            $constraint = $this->constraints[$name] ?? $inlineConstraint ?? '[^/]+';
            $pattern .= sprintf('(?P<%s>%s)', $name, $constraint);
            $offset = $position + strlen($placeholder);
        }

        $pattern .= preg_quote(substr($this->uri, $offset), '~');
        $this->compiledPattern = '~^' . $pattern . '$~uD';

        return $this->compiledPattern;
    }
}

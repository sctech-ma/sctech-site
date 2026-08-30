<?php

declare(strict_types=1);

namespace SCTech\Core;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

final class Container
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $shared = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    public function bind(string $id, mixed $concrete = null, bool $shared = false): void
    {
        $this->bindings[$id] = $concrete ?? $id;
        $this->shared[$id] = $shared;
        unset($this->instances[$id]);
    }

    public function singleton(string $id, mixed $concrete = null): void
    {
        $this->bind($id, $concrete, true);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        $this->shared[$id] = true;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->bindings)
            || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new RuntimeException(sprintf('Circular dependency detected while resolving "%s".', $id));
        }

        $this->resolving[$id] = true;

        try {
            $concrete = $this->bindings[$id] ?? $id;
            $object = $this->resolve($concrete);

            if (($this->shared[$id] ?? false) === true) {
                $this->instances[$id] = $object;
            }

            return $object;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * @param callable|array{0: object|string, 1: string}|string $callable
     * @param array<string, mixed> $parameters
     */
    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        [$resolvedCallable, $reflection] = $this->reflectCallable($callable);
        $arguments = $this->resolveParameters($reflection, $parameters);

        return $resolvedCallable(...$arguments);
    }

    private function resolve(mixed $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        if (is_object($concrete)) {
            return $concrete;
        }

        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new RuntimeException(sprintf('Container entry "%s" cannot be resolved.', (string) $concrete));
        }

        $reflection = new ReflectionClass($concrete);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(sprintf('Class "%s" is not instantiable.', $concrete));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $concrete();
        }

        return $reflection->newInstanceArgs($this->resolveParameters($constructor));
    }

    /**
     * @param callable|array{0: object|string, 1: string}|string $callable
     * @return array{0: Closure, 1: ReflectionFunctionAbstract}
     */
    private function reflectCallable(callable|array|string $callable): array
    {
        if (is_string($callable) && str_contains($callable, '@')) {
            [$class, $method] = explode('@', $callable, 2);
            $callable = [$class, $method];
        }

        if (is_array($callable)) {
            [$target, $method] = $callable;
            $target = is_string($target) ? $this->get($target) : $target;
            $resolved = [$target, $method];
            if (!is_callable($resolved)) {
                throw new RuntimeException(sprintf('Method "%s" is not callable.', $method));
            }

            return [Closure::fromCallable($resolved), new ReflectionMethod($target, $method)];
        }

        if (is_string($callable) && class_exists($callable)) {
            $object = $this->get($callable);
            if (!is_object($object) || !is_callable($object)) {
                throw new RuntimeException(sprintf('Class "%s" is not invokable.', $callable));
            }

            return [Closure::fromCallable($object), new ReflectionMethod($object, '__invoke')];
        }

        if (is_string($callable)) {
            if (!is_callable($callable)) {
                throw new RuntimeException(sprintf('Function "%s" is not callable.', $callable));
            }

            return [Closure::fromCallable($callable), new ReflectionFunction($callable)];
        }

        if ($callable instanceof Closure) {
            return [$callable, new ReflectionFunction($callable)];
        }

        if (!is_object($callable)) {
            throw new RuntimeException('Callable cannot be reflected.');
        }

        return [Closure::fromCallable($callable), new ReflectionMethod($callable, '__invoke')];
    }

    /**
     * @param array<string, mixed> $provided
     * @return list<mixed>
     */
    private function resolveParameters(ReflectionFunctionAbstract $reflection, array $provided = []): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                $variadic = $provided[$parameter->getName()] ?? [];
                if (!is_array($variadic)) {
                    $variadic = [$variadic];
                }
                array_push($arguments, ...array_values($variadic));
                continue;
            }

            if (array_key_exists($parameter->getName(), $provided)) {
                $arguments[] = $provided[$parameter->getName()];
                continue;
            }

            $className = $this->parameterClassName($parameter);
            if ($className !== null) {
                foreach ($provided as $value) {
                    if (is_object($value) && is_a($value, $className)) {
                        $arguments[] = $value;
                        continue 2;
                    }
                }

                if ($this->has($className)) {
                    $arguments[] = $this->get($className);
                    continue;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            $owner = $reflection->getName();
            throw new RuntimeException(sprintf(
                'Unable to resolve parameter "$%s" while calling "%s".',
                $parameter->getName(),
                $owner
            ));
        }

        return $arguments;
    }

    private function parameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                if ($namedType instanceof ReflectionNamedType && !$namedType->isBuiltin()) {
                    return $namedType->getName();
                }
            }
        }

        return null;
    }
}

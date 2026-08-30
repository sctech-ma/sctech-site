<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use RuntimeException;

final class Config
{
    /** @param array<string, mixed> $items */
    public function __construct(private array $items = [])
    {
    }

    public static function fromDirectory(string $directory): self
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Configuration directory "%s" does not exist.', $directory));
        }

        $items = [];
        $files = glob($directory . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $values = require $file;
            if (!is_array($values)) {
                throw new RuntimeException(sprintf('Configuration file "%s" must return an array.', $file));
            }

            $items[pathinfo($file, PATHINFO_FILENAME)] = $values;
        }

        return new self($items);
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $this->items;
        }

        $value = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function require(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new RuntimeException(sprintf('Required configuration key "%s" is missing.', $key));
        }

        return $this->get($key);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new RuntimeException(sprintf('Configuration key "%s" is not a string.', $key));
        }

        return (string) $value;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException(sprintf('Configuration key "%s" is not an integer.', $key));
        }

        return (int) $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new RuntimeException(sprintf('Configuration key "%s" is not a boolean.', $key));
        }

        return $parsed;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }
}

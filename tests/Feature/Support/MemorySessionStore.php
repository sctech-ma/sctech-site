<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\Services\SessionStore;

final class MemorySessionStore implements SessionStore
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
    }
}

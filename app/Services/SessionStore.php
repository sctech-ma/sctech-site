<?php

declare(strict_types=1);

namespace SCTech\Services;

interface SessionStore
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;

    public function regenerate(bool $deleteOldSession = true): void;
}

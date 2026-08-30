<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Core\Session;

final class CoreSessionStore implements SessionStore
{
    public function __construct(private readonly Session $session)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function put(string $key, mixed $value): void
    {
        $this->session->put($key, $value);
    }

    public function forget(string $key): void
    {
        $this->session->forget($key);
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        $this->session->regenerate($deleteOldSession);
    }
}

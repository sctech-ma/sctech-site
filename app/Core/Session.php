<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use RuntimeException;

final class Session
{
    /** @var array<string, mixed> */
    private array $data = [];
    private bool $started = false;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $initial
     */
    public function __construct(
        private readonly string $name = 'sctech_session',
        private readonly array $options = [],
        private readonly bool $native = true,
        array $initial = []
    ) {
        if (preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $name) !== 1) {
            throw new InvalidArgumentException('Invalid session name.');
        }

        $this->data = $initial;
    }

    /** @param array<string, mixed> $initial */
    public static function memory(array $initial = []): self
    {
        return new self('sctech_test', [], false, $initial);
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if ($this->native) {
            if (session_status() === PHP_SESSION_DISABLED) {
                throw new RuntimeException('PHP sessions are disabled.');
            }

            if (session_status() === PHP_SESSION_NONE) {
                $sameSite = match (strtolower((string) ($this->options['samesite'] ?? 'lax'))) {
                    'none' => 'None',
                    'strict' => 'Strict',
                    default => 'Lax',
                };
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_httponly', '1');
                ini_set('session.cookie_samesite', $sameSite);
                session_name($this->name);
                session_set_cookie_params([
                    'lifetime' => (int) ($this->options['lifetime'] ?? 0),
                    'path' => (string) ($this->options['path'] ?? '/'),
                    'domain' => (string) ($this->options['domain'] ?? ''),
                    'secure' => (bool) ($this->options['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => $sameSite,
                ]);

                if (session_start() !== true) {
                    throw new RuntimeException('Unable to start the session.');
                }
            }

            $this->data =& $_SESSION;
        }

        $this->started = true;
        $this->ageFlashData();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();

        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $this->data[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->ensureStarted();

        return $this->data;
    }

    public function put(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $this->data[$key] = $value;
    }

    /** @param array<string, mixed> $values */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put((string) $key, $value);
        }
    }

    public function forget(string $key): void
    {
        $this->ensureStarted();
        unset($this->data[$key]);
        $this->removeFlashReference($key);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $new = $this->data['_flash.new'] ?? [];
        $new[] = $key;
        $this->data['_flash.new'] = array_values(array_unique(array_map('strval', $new)));
    }

    /** @param list<string>|null $keys */
    public function keep(?array $keys = null): void
    {
        $this->ensureStarted();
        $old = array_map('strval', (array) ($this->data['_flash.old'] ?? []));
        $keys ??= $old;
        $new = array_map('strval', (array) ($this->data['_flash.new'] ?? []));
        $this->data['_flash.new'] = array_values(array_unique([...$new, ...array_intersect($old, $keys)]));
        $this->data['_flash.old'] = array_values(array_diff($old, $keys));
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        $this->ensureStarted();
        if ($this->native && session_status() === PHP_SESSION_ACTIVE && !session_regenerate_id($deleteOldSession)) {
            throw new RuntimeException('Unable to regenerate the session identifier.');
        }
    }

    public function invalidate(): void
    {
        $this->ensureStarted();
        $this->data = [];
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $this->data =& $_SESSION;
            $this->regenerate(true);
        }
    }

    public function commit(): void
    {
        if ($this->native && $this->started && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->started = false;
    }

    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    private function ageFlashData(): void
    {
        $old = array_map('strval', (array) ($this->data['_flash.old'] ?? []));
        foreach ($old as $key) {
            unset($this->data[$key]);
        }

        $this->data['_flash.old'] = array_values(array_unique(array_map(
            'strval',
            (array) ($this->data['_flash.new'] ?? [])
        )));
        $this->data['_flash.new'] = [];
    }

    private function removeFlashReference(string $key): void
    {
        foreach (['_flash.new', '_flash.old'] as $bucket) {
            $this->data[$bucket] = array_values(array_diff((array) ($this->data[$bucket] ?? []), [$key]));
        }
    }
}

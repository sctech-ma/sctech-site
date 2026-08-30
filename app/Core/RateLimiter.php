<?php

declare(strict_types=1);

namespace SCTech\Core;

use DateTimeImmutable;
use DateTimeZone;
use PDOException;

final class RateLimiter
{
    public function __construct(
        private readonly Database $database,
        private readonly string $secret,
        private readonly string $prefix = 'sctech'
    ) {
    }

    public function consume(
        string $key,
        int $limit,
        int $decaySeconds,
        ?DateTimeImmutable $now = null
    ): RateLimitResult {
        if ($limit < 1 || $decaySeconds < 1) {
            throw new \InvalidArgumentException('Rate limit and decay must be positive integers.');
        }

        if ($this->secret === '') {
            throw new \RuntimeException('A rate-limit HMAC secret is required.');
        }

        return $this->consumeAttempt($key, $limit, $decaySeconds, $now, false);
    }

    public function clear(string $key): void
    {
        $this->database->execute(
            'DELETE FROM rate_limits WHERE bucket_key = :bucket_key',
            ['bucket_key' => $this->hashKey($key)]
        );
    }

    public function prune(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->database->execute(
            'DELETE FROM rate_limits WHERE expires_at < :cutoff',
            ['cutoff' => $now->format('Y-m-d H:i:s.u')]
        );
    }

    private function consumeAttempt(
        string $key,
        int $limit,
        int $decaySeconds,
        ?DateTimeImmutable $now,
        bool $retried
    ): RateLimitResult {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $now = $now->setTimezone(new DateTimeZone('UTC'));
        $hash = $this->hashKey($key);

        try {
            return $this->database->transaction(function () use ($hash, $limit, $decaySeconds, $now): RateLimitResult {
                $lock = $this->database->driver() === 'mysql' ? ' FOR UPDATE' : '';
                $row = $this->database->selectOne(
                    'SELECT attempts, window_started_at, expires_at FROM rate_limits'
                    . ' WHERE bucket_key = :bucket_key' . $lock,
                    ['bucket_key' => $hash]
                );

                $nowString = $now->format('Y-m-d H:i:s.u');
                $expires = $now->modify('+' . $decaySeconds . ' seconds');
                if ($row === null) {
                    $attempts = 1;
                    $this->database->execute(
                        'INSERT INTO rate_limits'
                        . ' (bucket_key, attempts, window_started_at, expires_at, updated_at)'
                        . ' VALUES (:bucket_key, :attempts, :window_started_at, :expires_at, :updated_at)',
                        [
                            'bucket_key' => $hash,
                            'attempts' => $attempts,
                            'window_started_at' => $nowString,
                            'expires_at' => $expires->format('Y-m-d H:i:s.u'),
                            'updated_at' => $nowString,
                        ]
                    );
                } else {
                    $storedExpiry = new DateTimeImmutable((string) $row['expires_at'], new DateTimeZone('UTC'));
                    if ($storedExpiry <= $now) {
                        $attempts = 1;
                    } else {
                        $attempts = (int) $row['attempts'] + 1;
                        $expires = $storedExpiry;
                    }

                    $this->database->execute(
                        'UPDATE rate_limits SET attempts = :attempts, window_started_at = :window_started_at,'
                        . ' expires_at = :expires_at, updated_at = :updated_at WHERE bucket_key = :bucket_key',
                        [
                            'bucket_key' => $hash,
                            'attempts' => $attempts,
                            'window_started_at' => $attempts === 1
                                ? $nowString
                                : (string) $row['window_started_at'],
                            'expires_at' => $expires->format('Y-m-d H:i:s.u'),
                            'updated_at' => $nowString,
                        ]
                    );
                }

                $retryAfter = max(0, $expires->getTimestamp() - $now->getTimestamp());

                return new RateLimitResult(
                    $attempts <= $limit,
                    $limit,
                    $attempts,
                    max(0, $limit - $attempts),
                    $attempts <= $limit ? 0 : $retryAfter,
                    $expires->getTimestamp()
                );
            });
        } catch (PDOException $exception) {
            if (!$retried && in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return $this->consumeAttempt($key, $limit, $decaySeconds, $now, true);
            }
            throw $exception;
        }
    }

    private function hashKey(string $key): string
    {
        return hash_hmac('sha256', $this->prefix . '|' . $key, $this->secret);
    }
}

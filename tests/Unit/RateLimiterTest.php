<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use SCTech\Core\Database;
use SCTech\Core\RateLimiter;

#[RequiresPhpExtension('pdo_sqlite')]
final class RateLimiterTest extends TestCase
{
    public function testLimitIsPersistentAndResetsAfterTheWindow(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(
            'CREATE TABLE rate_limits ('
            . 'bucket_key TEXT PRIMARY KEY, attempts INTEGER NOT NULL, window_started_at TEXT NOT NULL,'
            . 'expires_at TEXT NOT NULL, updated_at TEXT NOT NULL)'
        );
        $limiter = new RateLimiter(new Database($pdo), 'test-secret');
        $now = new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC'));

        self::assertTrue($limiter->consume('contact|203.0.113.1', 2, 60, $now)->allowed);
        self::assertTrue($limiter->consume('contact|203.0.113.1', 2, 60, $now)->allowed);
        $blocked = $limiter->consume('contact|203.0.113.1', 2, 60, $now);
        self::assertFalse($blocked->allowed);
        self::assertSame(0, $blocked->remaining);
        self::assertSame(60, $blocked->retryAfter);

        $reset = $limiter->consume('contact|203.0.113.1', 2, 60, $now->modify('+61 seconds'));
        self::assertTrue($reset->allowed);
        self::assertSame(1, $reset->attempts);
    }

    public function testRawIdentityIsNeverStored(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(
            'CREATE TABLE rate_limits ('
            . 'bucket_key TEXT PRIMARY KEY, attempts INTEGER NOT NULL, window_started_at TEXT NOT NULL,'
            . 'expires_at TEXT NOT NULL, updated_at TEXT NOT NULL)'
        );
        $limiter = new RateLimiter(new Database($pdo), 'test-secret');
        $limiter->consume('contact|karim@example.test|203.0.113.1', 5, 900);

        $stored = (string) $pdo->query('SELECT bucket_key FROM rate_limits')->fetchColumn();
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $stored);
        self::assertStringNotContainsString('karim', $stored);
    }
}

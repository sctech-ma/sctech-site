<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use DateTimeImmutable;
use PDO;

final class RateLimitRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{allowed: bool, attempts: int, retry_after: int} */
    public function consume(string $bucketKey, int $limit, int $windowSeconds, DateTimeImmutable $now): array
    {
        $window = intdiv($now->getTimestamp(), $windowSeconds) * $windowSeconds;
        $startedAt = (new DateTimeImmutable('@' . $window))->setTimezone($now->getTimezone());
        $expiresAt = $startedAt->modify(sprintf('+%d seconds', $windowSeconds));

        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO rate_limits (bucket_key, attempts, window_started_at, expires_at)
            VALUES (:bucket_key, 1, :window_started_at, :expires_at)
            ON DUPLICATE KEY UPDATE attempts = attempts + 1, updated_at = UTC_TIMESTAMP(6)
            SQL);
        $statement->execute([
            'bucket_key' => $bucketKey,
            'window_started_at' => $startedAt->format('Y-m-d H:i:s.u'),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s.u'),
        ]);

        $read = $this->pdo->prepare('SELECT attempts FROM rate_limits WHERE bucket_key = :bucket_key');
        $read->execute(['bucket_key' => $bucketKey]);
        $attempts = (int) $read->fetchColumn();

        return [
            'allowed' => $attempts <= $limit,
            'attempts' => $attempts,
            'retry_after' => max(1, $expiresAt->getTimestamp() - $now->getTimestamp()),
        ];
    }

    public function purgeExpired(DateTimeImmutable $now): int
    {
        $statement = $this->pdo->prepare('DELETE FROM rate_limits WHERE expires_at < :now');
        $statement->execute(['now' => $now->format('Y-m-d H:i:s.u')]);

        return $statement->rowCount();
    }
}

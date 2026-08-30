<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use DateTimeImmutable;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findActiveByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT id, email, password_hash, display_name, role, status, version
            FROM users
            WHERE email = :email AND status = 'active'
            LIMIT 1
            SQL);
        $statement->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function markLoggedIn(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP(6) WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function recordLoginAttempt(string $identityHash, string $ipHash, bool $succeeded): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO login_attempts (identity_hash, ip_hash, succeeded)
            VALUES (:identity_hash, :ip_hash, :succeeded)
            SQL);
        $statement->execute([
            'identity_hash' => $identityHash,
            'ip_hash' => $ipHash,
            'succeeded' => $succeeded ? 1 : 0,
        ]);
    }

    public function failedIdentityAttemptsSince(string $identityHash, string $ipHash, DateTimeImmutable $since): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM login_attempts
            WHERE identity_hash = :identity_hash AND ip_hash = :ip_hash
              AND succeeded = 0 AND attempted_at >= :since
            SQL);
        $statement->execute([
            'identity_hash' => $identityHash,
            'ip_hash' => $ipHash,
            'since' => $since->format('Y-m-d H:i:s.u'),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function failedIpAttemptsSince(string $ipHash, DateTimeImmutable $since): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM login_attempts
            WHERE ip_hash = :ip_hash AND succeeded = 0 AND attempted_at >= :since
            SQL);
        $statement->execute([
            'ip_hash' => $ipHash,
            'since' => $since->format('Y-m-d H:i:s.u'),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function create(string $email, string $passwordHash, string $displayName, string $role = 'admin'): int
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            throw new \InvalidArgumentException('Unsupported role.');
        }
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO users (email, password_hash, display_name, role)
            VALUES (:email, :password_hash, :display_name, :role)
            SQL);
        $statement->execute([
            'email' => mb_strtolower(trim($email), 'UTF-8'),
            'password_hash' => $passwordHash,
            'display_name' => trim($displayName),
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function purgeAttemptsBefore(DateTimeImmutable $before): int
    {
        $statement = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :before');
        $statement->execute(['before' => $before->format('Y-m-d H:i:s.u')]);

        return $statement->rowCount();
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\DTO\AdminLogin;
use SCTech\Repositories\AdminAuditRepository;
use SCTech\Repositories\UserRepository;

final class AdminAuthService
{
    private const DUMMY_HASH = '$2y$12$wWyrOXJJDwS7kKvBKH0Q1e5vZzoF68l01eMtQ0J44t/DxBZFQVWZu';

    public function __construct(
        private readonly UserRepository $users,
        private readonly SessionStore $session,
        private readonly Clock $clock,
        private readonly SubmissionHasher $hasher,
        private readonly AdminAuditRepository $audit,
        private readonly int $idleSeconds = 1800,
        private readonly int $absoluteSeconds = 28800,
    ) {
    }

    public function attempt(AdminLogin $credentials, string $ipAddress, string $requestId = ''): AuthResult
    {
        $now = $this->clock->now();
        $identityHash = $this->hasher->identity($credentials->email);
        $ipHash = $this->hasher->ip($ipAddress);
        $since = $now->modify('-15 minutes');
        $locked = $this->users->failedIdentityAttemptsSince($identityHash, $ipHash, $since) >= 5
            || $this->users->failedIpAttemptsSince($ipHash, $since) >= 20;
        if ($locked) {
            password_verify($credentials->password, self::DUMMY_HASH);
            $this->audit->record(null, 'auth.login_blocked', 'user', null, $requestId, $ipHash);

            return new AuthResult(false, null, true);
        }

        $record = $this->users->findActiveByEmail($credentials->email);
        if ($record === null) {
            password_verify($credentials->password, self::DUMMY_HASH);
            $this->users->recordLoginAttempt($identityHash, $ipHash, false);
            $this->audit->record(null, 'auth.login_failed', 'user', null, $requestId, $ipHash);

            return new AuthResult(false);
        }
        $valid = password_verify($credentials->password, (string) $record['password_hash']);
        $this->users->recordLoginAttempt($identityHash, $ipHash, $valid);
        if (!$valid) {
            $this->audit->record(null, 'auth.login_failed', 'user', null, $requestId, $ipHash);

            return new AuthResult(false);
        }

        $user = [
            'id' => (int) $record['id'],
            'role' => (string) $record['role'],
            'email' => (string) $record['email'],
            'display_name' => (string) $record['display_name'],
        ];
        $this->session->regenerate(true);
        $this->session->put('auth.user', $user);
        $this->session->put('auth.started_at', $now->getTimestamp());
        $this->session->put('auth.last_seen_at', $now->getTimestamp());
        $this->users->markLoggedIn($user['id']);
        $this->audit->record($user['id'], 'auth.login_succeeded', 'user', $user['id'], $requestId, $ipHash);

        return new AuthResult(true, $user);
    }

    /** @return array{id:int,role:string,email:string,display_name:string}|null */
    public function user(): ?array
    {
        $user = $this->session->get('auth.user');
        if (!is_array($user) || !isset($user['id'], $user['role'], $user['email'])) {
            return null;
        }
        $now = $this->clock->now()->getTimestamp();
        $started = (int) $this->session->get('auth.started_at', 0);
        $lastSeen = (int) $this->session->get('auth.last_seen_at', 0);
        if ($started <= 0 || $lastSeen <= 0 || ($now - $started) > $this->absoluteSeconds || ($now - $lastSeen) > $this->idleSeconds) {
            $this->logout();

            return null;
        }
        $this->session->put('auth.last_seen_at', $now);

        return [
            'id' => (int) $user['id'],
            'role' => (string) $user['role'],
            'email' => (string) $user['email'],
            'display_name' => (string) ($user['display_name'] ?? $user['email']),
        ];
    }

    public function can(string $capability): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }
        if ($user['role'] === 'admin') {
            return true;
        }

        return in_array($capability, ['content.view', 'content.edit', 'media.view', 'media.upload', 'preview'], true);
    }

    public function logout(string $requestId = '', string $ipAddress = ''): void
    {
        $user = $this->session->get('auth.user');
        $userId = is_array($user) && isset($user['id']) ? (int) $user['id'] : null;
        if ($userId !== null) {
            $this->audit->record($userId, 'auth.logout', 'user', $userId, $requestId, $this->hasher->ip($ipAddress));
        }
        $this->session->forget('auth.user');
        $this->session->forget('auth.started_at');
        $this->session->forget('auth.last_seen_at');
        $this->session->regenerate(true);
    }
}

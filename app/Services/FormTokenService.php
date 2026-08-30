<?php

declare(strict_types=1);

namespace SCTech\Services;

final class FormTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
        private readonly int $minimumAgeSeconds = 3,
        private readonly int $maximumAgeSeconds = 7200,
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The form HMAC secret must contain at least 32 bytes.');
        }
    }

    public function issueTimingToken(string $action): string
    {
        $payload = implode('|', [$action, (string) $this->clock->now()->getTimestamp(), bin2hex(random_bytes(12))]);
        $signature = hash_hmac('sha256', $payload, $this->secret, true);

        return self::base64UrlEncode($payload) . '.' . self::base64UrlEncode($signature);
    }

    public function verifyTimingToken(string $action, string $token): bool
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        $payload = self::base64UrlDecode($parts[0]);
        $signature = self::base64UrlDecode($parts[1]);
        if ($payload === null || $signature === null) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $this->secret, true);
        if (!hash_equals($expected, $signature)) {
            return false;
        }
        [$tokenAction, $issuedAt, $nonce] = array_pad(explode('|', $payload, 3), 3, '');
        if ($tokenAction !== $action || !ctype_digit($issuedAt) || !preg_match('/^[a-f0-9]{24}$/', $nonce)) {
            return false;
        }
        $age = $this->clock->now()->getTimestamp() - (int) $issuedAt;

        return $age >= $this->minimumAgeSeconds && $age <= $this->maximumAgeSeconds;
    }

    public function issueIdempotencyKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function validIdempotencyKey(string $key): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{32,128}$/', $key) === 1;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            return null;
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}

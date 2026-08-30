<?php

declare(strict_types=1);

namespace SCTech\Services;

final class SubmissionHasher
{
    public function __construct(private readonly string $secret)
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The privacy HMAC secret must contain at least 32 bytes.');
        }
    }

    public function idempotency(string $action, string $key): string
    {
        return hash_hmac('sha256', $action . '|' . $key, $this->secret);
    }

    public function ip(string $ip): string
    {
        return hash_hmac('sha256', 'ip|' . trim($ip), $this->secret);
    }

    public function userAgent(string $userAgent): string
    {
        return $userAgent === '' ? '' : hash_hmac('sha256', 'ua|' . $userAgent, $this->secret);
    }

    public function identity(string $identity): string
    {
        return hash_hmac('sha256', 'identity|' . mb_strtolower(trim($identity), 'UTF-8'), $this->secret);
    }
}

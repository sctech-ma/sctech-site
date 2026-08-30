<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class AdminLogin
{
    public function __construct(
        public string $email,
        public string $password,
        public string $csrfToken,
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $email = is_scalar($input['email'] ?? null) ? (string) $input['email'] : '';
        $password = is_scalar($input['password'] ?? null) ? (string) $input['password'] : '';
        $csrfValue = $input['_csrf'] ?? $input['_token'] ?? '';
        $csrf = is_scalar($csrfValue) ? (string) $csrfValue : '';

        return new self(mb_strtolower(trim($email), 'UTF-8'), $password, trim($csrf));
    }
}

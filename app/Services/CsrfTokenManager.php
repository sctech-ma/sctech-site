<?php

declare(strict_types=1);

namespace SCTech\Services;

final class CsrfTokenManager
{
    private const SESSION_KEY = 'security.csrf';

    public function __construct(private readonly SessionStore $session)
    {
    }

    public function issue(string $action): string
    {
        $tokens = $this->tokens();
        $token = bin2hex(random_bytes(32));
        $tokens[$action] = $token;
        $this->session->put(self::SESSION_KEY, $tokens);

        return $token;
    }

    public function token(string $action): string
    {
        $tokens = $this->tokens();

        return is_string($tokens[$action] ?? null) ? $tokens[$action] : $this->issue($action);
    }

    public function validate(string $action, string $candidate, bool $consume = true): bool
    {
        $tokens = $this->tokens();
        $stored = $tokens[$action] ?? null;
        $valid = is_string($stored) && $candidate !== '' && hash_equals($stored, $candidate);
        if ($valid && $consume) {
            unset($tokens[$action]);
            $this->session->put(self::SESSION_KEY, $tokens);
        }

        return $valid;
    }

    /** @return array<string, string> */
    private function tokens(): array
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);

        return is_array($tokens) ? array_filter($tokens, 'is_string') : [];
    }
}

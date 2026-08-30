<?php

declare(strict_types=1);

namespace SCTech\Core;

use SCTech\Helpers\Escaper;

final class Csrf
{
    public function __construct(
        private readonly Session $session,
        private readonly string $sessionKey = '_csrf_token'
    ) {
    }

    public function token(): string
    {
        $token = $this->session->get($this->sessionKey);
        if (!is_string($token) || preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) !== 1) {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $this->session->put($this->sessionKey, $token);
        }

        return $token;
    }

    public function verify(?string $provided): bool
    {
        if (!is_string($provided) || $provided === '') {
            return false;
        }

        $expected = $this->session->get($this->sessionKey);

        return is_string($expected) && strlen($expected) === strlen($provided) && hash_equals($expected, $provided);
    }

    public function rotate(): string
    {
        $this->session->forget($this->sessionKey);

        return $this->token();
    }

    public function field(string $field = '_token'): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            Escaper::attribute($field),
            Escaper::attribute($this->token())
        );
    }

    public function tokenFromRequest(Request $request): ?string
    {
        $token = $request->body('_token');
        if (!is_string($token) || $token === '') {
            $token = $request->header('X-CSRF-Token');
        }

        return is_string($token) ? $token : null;
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Validation\ValidationResult;

final class FormSecurityGate
{
    public function __construct(
        private readonly CsrfTokenManager $csrf,
        private readonly FormTokenService $tokens,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    public function check(
        string $action,
        string $csrfToken,
        string $timingToken,
        string $idempotencyKey,
        string $honeypot,
        string $subjectHash,
    ): FormSecurityResult {
        $errors = [];
        if ($honeypot !== '') {
            $errors['_form'][] = 'La demande n’a pas pu être vérifiée. Rechargez la page puis réessayez.';
        }
        if (!$this->csrf->validate($action, $csrfToken)) {
            $errors['_form'][] = 'Votre session a expiré. Rechargez la page puis réessayez.';
        }
        if (!$this->tokens->verifyTimingToken($action, $timingToken)) {
            $errors['_form'][] = 'La demande a été envoyée trop rapidement ou le formulaire a expiré.';
        }
        if (!$this->tokens->validIdempotencyKey($idempotencyKey)) {
            $errors['_form'][] = 'Le formulaire est incomplet. Rechargez la page puis réessayez.';
        }
        if ($errors !== []) {
            return new FormSecurityResult(ValidationResult::invalid($errors));
        }

        $rate = $this->rateLimiter->consume($action, $subjectHash);
        if (!$rate->allowed) {
            return new FormSecurityResult(
                ValidationResult::invalid(['_form' => ['Trop de demandes ont été envoyées. Réessayez un peu plus tard.']]),
                $rate->retryAfter,
            );
        }

        return new FormSecurityResult(ValidationResult::valid());
    }
}

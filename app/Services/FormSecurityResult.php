<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Validation\ValidationResult;

final readonly class FormSecurityResult
{
    public function __construct(
        public ValidationResult $validation,
        public int $retryAfter = 0,
    ) {
    }
}

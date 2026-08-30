<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Validation\ValidationResult;

final readonly class ContentSaveResult
{
    public function __construct(
        public bool $saved,
        public ?int $id,
        public ValidationResult $validation,
    ) {
    }
}

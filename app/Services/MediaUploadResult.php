<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Validation\ValidationResult;

final readonly class MediaUploadResult
{
    public function __construct(
        public bool $stored,
        public ?int $id,
        public ValidationResult $validation,
    ) {
    }
}

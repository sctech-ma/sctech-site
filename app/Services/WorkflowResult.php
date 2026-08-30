<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Validation\ValidationResult;

final readonly class WorkflowResult
{
    public function __construct(
        public bool $successful,
        public bool $persisted,
        public bool $duplicate,
        public ?string $publicId,
        public ValidationResult $validation,
        public int $retryAfter = 0,
        public bool $notificationPending = false,
    ) {
    }

    public static function invalid(ValidationResult $validation, int $retryAfter = 0): self
    {
        return new self(false, false, false, null, $validation, $retryAfter);
    }
}

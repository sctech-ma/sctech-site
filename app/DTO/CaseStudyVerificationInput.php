<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class CaseStudyVerificationInput
{
    public function __construct(
        public int $actorId,
        public string $actorRole,
        public bool $approve,
        public string $notes,
    ) {
    }

    public function isAdministrator(): bool
    {
        return $this->actorRole === 'admin';
    }
}

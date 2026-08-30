<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use SCTech\DTO\PersistedLead;
use SCTech\DTO\QuoteSubmission;
use SCTech\DTO\SubmissionContext;

interface QuoteRequestStore
{
    public function findByIdempotencyHash(string $hash): ?PersistedLead;

    public function create(
        QuoteSubmission $submission,
        SubmissionContext $context,
        string $idempotencyHash,
        string $ipHash,
        string $userAgentHash,
    ): PersistedLead;

    public function markNotificationSent(int $id): void;

    public function markNotificationFailed(int $id, string $safeErrorCode): void;
}

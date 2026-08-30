<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\DTO\ContactSubmission;
use SCTech\DTO\PersistedLead;
use SCTech\DTO\SubmissionContext;
use SCTech\Repositories\ContactMessageStore;

final class InMemoryContactStore implements ContactMessageStore
{
    public int $createCount = 0;
    public string $status = '';
    private ?PersistedLead $lead = null;

    public function findByIdempotencyHash(string $hash): ?PersistedLead
    {
        return $this->lead === null
            ? null
            : new PersistedLead($this->lead->id, $this->lead->publicId, $this->status, true);
    }

    public function create(
        ContactSubmission $submission,
        SubmissionContext $context,
        string $idempotencyHash,
        string $ipHash,
        string $userAgentHash,
    ): PersistedLead {
        ++$this->createCount;
        $this->status = 'pending';
        $this->lead = new PersistedLead(1, '00000000-0000-4000-8000-000000000001', 'pending');

        return $this->lead;
    }

    public function markNotificationSent(int $id): void
    {
        $this->status = 'sent';
    }

    public function markNotificationFailed(int $id, string $safeErrorCode): void
    {
        $this->status = 'failed';
    }
}

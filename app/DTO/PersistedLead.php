<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class PersistedLead
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $notificationStatus,
        public bool $duplicate = false,
    ) {
    }
}

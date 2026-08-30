<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class SubmissionContext
{
    public function __construct(
        public string $ipAddress,
        public string $userAgent,
        public string $requestId,
        public string $policyVersion = '2026-08-03',
    ) {
    }
}

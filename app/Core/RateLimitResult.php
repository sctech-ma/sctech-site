<?php

declare(strict_types=1);

namespace SCTech\Core;

final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $attempts,
        public int $remaining,
        public int $retryAfter,
        public int $resetsAt
    ) {
    }
}

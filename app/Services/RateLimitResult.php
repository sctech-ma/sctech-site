<?php

declare(strict_types=1);

namespace SCTech\Services;

final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $retryAfter = 0,
    ) {
    }
}

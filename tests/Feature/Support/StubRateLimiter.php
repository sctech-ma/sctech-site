<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\Services\RateLimiter;
use SCTech\Services\RateLimitResult;

final readonly class StubRateLimiter implements RateLimiter
{
    public function __construct(private bool $allowed, private int $retryAfter = 0)
    {
    }

    public function consume(string $action, string $subjectHash): RateLimitResult
    {
        return new RateLimitResult($this->allowed, $this->retryAfter);
    }
}

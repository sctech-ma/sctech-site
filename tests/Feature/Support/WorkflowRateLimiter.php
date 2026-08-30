<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\Services\RateLimiter;
use SCTech\Services\RateLimitResult;

final class WorkflowRateLimiter implements RateLimiter
{
    public function consume(string $action, string $subjectHash): RateLimitResult
    {
        return new RateLimitResult(true);
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Services;

interface RateLimiter
{
    public function consume(string $action, string $subjectHash): RateLimitResult;
}

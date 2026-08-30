<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\Repositories\RateLimitRepository;

final class PersistentRateLimiter implements RateLimiter
{
    public function __construct(
        private readonly RateLimitRepository $repository,
        private readonly Clock $clock,
        private readonly string $secret,
        private readonly int $quarterHourLimit = 5,
        private readonly int $dailyLimit = 20,
    ) {
    }

    public function consume(string $action, string $subjectHash): RateLimitResult
    {
        $now = $this->clock->now();
        $shortKey = hash_hmac('sha256', $action . '|900|' . $subjectHash . '|' . intdiv($now->getTimestamp(), 900), $this->secret);
        $short = $this->repository->consume($shortKey, $this->quarterHourLimit, 900, $now);
        if (!$short['allowed']) {
            return new RateLimitResult(false, $short['retry_after']);
        }

        $dailyKey = hash_hmac('sha256', $action . '|86400|' . $subjectHash . '|' . intdiv($now->getTimestamp(), 86400), $this->secret);
        $daily = $this->repository->consume($dailyKey, $this->dailyLimit, 86400, $now);

        return new RateLimitResult($daily['allowed'], $daily['allowed'] ? 0 : $daily['retry_after']);
    }
}

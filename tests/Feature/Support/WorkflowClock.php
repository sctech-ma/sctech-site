<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use DateTimeImmutable;
use SCTech\Services\Clock;

final class WorkflowClock implements Clock
{
    public function __construct(private DateTimeImmutable $time)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }

    public function advance(string $modifier): void
    {
        $this->time = $this->time->modify($modifier);
    }
}

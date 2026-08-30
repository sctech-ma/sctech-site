<?php

declare(strict_types=1);

namespace SCTech\Services;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}

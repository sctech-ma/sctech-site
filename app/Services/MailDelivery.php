<?php

declare(strict_types=1);

namespace SCTech\Services;

final readonly class MailDelivery
{
    public function __construct(
        public bool $delivered,
        public string $safeErrorCode = '',
    ) {
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Services;

final readonly class MailMessage
{
    public function __construct(
        public string $subject,
        public string $htmlBody,
        public string $textBody,
        public string $replyToAddress,
        public string $replyToName,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Services;

interface MailTransport
{
    public function send(MailMessage $message): MailDelivery;
}

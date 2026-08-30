<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\Services\MailDelivery;
use SCTech\Services\MailMessage;
use SCTech\Services\MailTransport;

final class WorkflowMailTransport implements MailTransport
{
    public bool $observedPersistedLead = false;
    public ?MailMessage $lastMessage = null;

    public function __construct(
        private readonly InMemoryContactStore $store,
        public MailDelivery $next,
    ) {
    }

    public function send(MailMessage $message): MailDelivery
    {
        $this->observedPersistedLead = $this->store->createCount > 0;
        $this->lastMessage = $message;

        return $this->next;
    }
}

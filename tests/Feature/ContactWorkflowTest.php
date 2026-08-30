<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use SCTech\DTO\ContactSubmission;
use SCTech\DTO\SubmissionContext;
use SCTech\Services\ContactWorkflow;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\FormSecurityGate;
use SCTech\Services\FormTokenService;
use SCTech\Services\LeadNotificationService;
use SCTech\Services\MailDelivery;
use SCTech\Services\SubmissionHasher;
use SCTech\Tests\Feature\Support\InMemoryContactStore;
use SCTech\Tests\Feature\Support\WorkflowClock;
use SCTech\Tests\Feature\Support\WorkflowFixture;
use SCTech\Tests\Feature\Support\WorkflowMailTransport;
use SCTech\Tests\Feature\Support\WorkflowRateLimiter;
use SCTech\Tests\Feature\Support\WorkflowSessionStore;
use SCTech\Validation\ContactValidator;

final class ContactWorkflowTest extends TestCase
{
    private const SECRET = 'workflow-test-secret-with-32-bytes-minimum';

    public function testLeadIsPersistedBeforeSmtpAndMarkedSent(): void
    {
        $fixture = $this->fixture(new MailDelivery(true));
        $result = $fixture->workflow->submit($fixture->submission, $fixture->context);

        self::assertTrue($result->successful);
        self::assertTrue($result->persisted);
        self::assertSame('sent', $fixture->store->status);
        self::assertTrue($fixture->transport->observedPersistedLead);
        self::assertSame(1, $fixture->store->createCount);
        self::assertSame('contact@example.test', $fixture->transport->lastMessage?->replyToAddress);
    }

    public function testRequiredMailFailureKeepsOneRetryableRecord(): void
    {
        $fixture = $this->fixture(new MailDelivery(false, 'delivery_failed'));
        $first = $fixture->workflow->submit($fixture->submission, $fixture->context);

        self::assertFalse($first->successful);
        self::assertTrue($first->persisted);
        self::assertTrue($first->notificationPending);
        self::assertSame('failed', $fixture->store->status);

        $fixture->transport->next = new MailDelivery(true);
        $retry = $fixture->resubmitWithFreshSecurityFields();
        self::assertTrue($retry->successful);
        self::assertTrue($retry->duplicate);
        self::assertSame('sent', $fixture->store->status);
        self::assertSame(1, $fixture->store->createCount, 'The retry must not create another lead.');
    }

    public function testMailCanBeOptionalWithoutPretendingItWasDelivered(): void
    {
        $fixture = $this->fixture(new MailDelivery(false, 'transport_not_configured'), false);
        $result = $fixture->workflow->submit($fixture->submission, $fixture->context);

        self::assertTrue($result->successful);
        self::assertTrue($result->notificationPending);
        self::assertSame('failed', $fixture->store->status);
    }

    private function fixture(MailDelivery $delivery, bool $mailRequired = true): WorkflowFixture
    {
        $clock = new WorkflowClock(new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC')));
        $session = new WorkflowSessionStore();
        $csrf = new CsrfTokenManager($session);
        $tokens = new FormTokenService(self::SECRET, $clock);
        $hasher = new SubmissionHasher(self::SECRET);
        $store = new InMemoryContactStore();
        $transport = new WorkflowMailTransport($store, $delivery);
        $workflow = new ContactWorkflow(
            new ContactValidator(),
            new FormSecurityGate($csrf, $tokens, new WorkflowRateLimiter()),
            $hasher,
            $store,
            new LeadNotificationService($transport),
            $mailRequired,
        );
        $idempotencyKey = $tokens->issueIdempotencyKey();
        $submission = $this->submission($csrf->issue('contact'), $tokens->issueTimingToken('contact'), $idempotencyKey);
        $clock->advance('+3 seconds');

        return new WorkflowFixture(
            $workflow,
            $store,
            $transport,
            $clock,
            $csrf,
            $tokens,
            $idempotencyKey,
            $submission,
            new SubmissionContext('203.0.113.10', 'PHPUnit', 'request-test', 'policy-test'),
        );
    }

    private function submission(string $csrf, string $timing, string $idempotency): ContactSubmission
    {
        return new ContactSubmission(
            'Contact Test',
            'contact@example.test',
            'Organisation Test',
            '+212 600 000 000',
            'cloud',
            'Nous souhaitons cadrer une plateforme cloud gouvernée et observable.',
            true,
            'fr',
            $csrf,
            $timing,
            $idempotency,
            '',
        );
    }
}

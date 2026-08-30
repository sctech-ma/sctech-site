<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature\Support;

use SCTech\DTO\ContactSubmission;
use SCTech\DTO\SubmissionContext;
use SCTech\Services\ContactWorkflow;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\FormTokenService;
use SCTech\Services\WorkflowResult;

final readonly class WorkflowFixture
{
    public function __construct(
        public ContactWorkflow $workflow,
        public InMemoryContactStore $store,
        public WorkflowMailTransport $transport,
        public WorkflowClock $clock,
        public CsrfTokenManager $csrf,
        public FormTokenService $tokens,
        public string $idempotencyKey,
        public ContactSubmission $submission,
        public SubmissionContext $context,
    ) {
    }

    public function resubmitWithFreshSecurityFields(): WorkflowResult
    {
        $submission = new ContactSubmission(
            $this->submission->fullName,
            $this->submission->email,
            $this->submission->organisation,
            $this->submission->phone,
            $this->submission->subject,
            $this->submission->message,
            $this->submission->consentPrivacy,
            $this->submission->locale,
            $this->csrf->issue('contact'),
            $this->tokens->issueTimingToken('contact'),
            $this->idempotencyKey,
            '',
        );
        $this->clock->advance('+3 seconds');

        return $this->workflow->submit($submission, $this->context);
    }
}

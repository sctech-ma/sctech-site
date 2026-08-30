<?php

declare(strict_types=1);

namespace SCTech\Services;

use PDOException;
use SCTech\DTO\PersistedLead;
use SCTech\DTO\QuoteSubmission;
use SCTech\DTO\SubmissionContext;
use SCTech\Repositories\QuoteRequestStore;
use SCTech\Validation\QuoteValidator;
use SCTech\Validation\ValidationResult;

final class QuoteWorkflow
{
    public function __construct(
        private readonly QuoteValidator $validator,
        private readonly FormSecurityGate $security,
        private readonly SubmissionHasher $hasher,
        private readonly QuoteRequestStore $repository,
        private readonly LeadNotificationService $notifications,
        private readonly bool $mailRequired = true,
    ) {
    }

    public function submit(QuoteSubmission $submission, SubmissionContext $context): WorkflowResult
    {
        $validation = $this->validator->validate($submission);
        if (!$validation->isValid()) {
            return WorkflowResult::invalid($validation);
        }
        $ipHash = $this->hasher->ip($context->ipAddress);
        $security = $this->security->check(
            'quote',
            $submission->csrfToken,
            $submission->timingToken,
            $submission->idempotencyKey,
            $submission->website,
            $ipHash,
        );
        if (!$security->validation->isValid()) {
            return WorkflowResult::invalid($security->validation, $security->retryAfter);
        }

        $idempotencyHash = $this->hasher->idempotency('quote', $submission->idempotencyKey);
        $lead = $this->repository->findByIdempotencyHash($idempotencyHash);
        if ($lead === null) {
            try {
                $lead = $this->repository->create(
                    $submission,
                    $context,
                    $idempotencyHash,
                    $ipHash,
                    $this->hasher->userAgent($context->userAgent),
                );
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() !== '23000') {
                    throw $exception;
                }
                $lead = $this->repository->findByIdempotencyHash($idempotencyHash);
                if (!$lead instanceof PersistedLead) {
                    throw $exception;
                }
            }
        }
        if ($lead->notificationStatus === 'sent') {
            return new WorkflowResult(true, true, true, $lead->publicId, ValidationResult::valid());
        }
        $delivery = $this->notifications->quote($submission, $lead);
        if ($delivery->delivered) {
            $this->repository->markNotificationSent($lead->id);

            return new WorkflowResult(true, true, $lead->duplicate, $lead->publicId, ValidationResult::valid());
        }
        $this->repository->markNotificationFailed($lead->id, $delivery->safeErrorCode ?: 'delivery_failed');
        $success = !$this->mailRequired;
        $validation = $success
            ? ValidationResult::valid()
            : ValidationResult::invalid(['_form' => ['Votre demande est enregistrée, mais sa notification reste en attente. Réessayez avec le même formulaire.']]);

        return new WorkflowResult($success, true, $lead->duplicate, $lead->publicId, $validation, 0, true);
    }
}

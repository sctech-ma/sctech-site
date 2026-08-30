<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\DTO\CaseStudyVerificationInput;
use SCTech\DTO\ContentInput;
use SCTech\Repositories\AdminAuditRepository;
use SCTech\Repositories\CaseStudyPublicationException;
use SCTech\Repositories\ContentRepository;
use SCTech\Validation\CaseStudyVerificationPolicy;
use SCTech\Validation\ContentValidator;
use SCTech\Validation\ValidationResult;

final class ContentService
{
    public function __construct(
        private readonly ContentRepository $content,
        private readonly ContentValidator $validator,
        private readonly AdminAuditRepository $audit,
        private readonly CaseStudyVerificationPolicy $caseStudyPolicy,
    ) {
    }

    public function save(
        string $type,
        ContentInput $input,
        int $actorId,
        string $actorRole,
        string $requestId,
        string $ipHash,
    ): ContentSaveResult {
        $validation = $this->validator->validate($input, $type);
        $verification = null;
        if ($type === 'realisations') {
            $existing = $input->id !== null ? $this->content->find($type, $input->id) : null;
            $validation = $validation->merge($this->caseStudyPolicy->validate($input, $actorRole, $existing));
            $verification = new CaseStudyVerificationInput(
                $actorId,
                $actorRole,
                $input->verifyForPublication,
                $input->verificationNotes,
            );
        }
        if (!$validation->isValid()) {
            return new ContentSaveResult(false, $input->id, $validation);
        }

        try {
            $id = $this->content->save($type, $input, $verification);
        } catch (CaseStudyPublicationException $exception) {
            return new ContentSaveResult(
                false,
                $input->id,
                ValidationResult::invalid(['status' => [$exception->getMessage()]]),
            );
        }
        $this->audit->record(
            $actorId,
            $input->id === null ? 'content.created' : 'content.updated',
            $type,
            $id,
            $requestId,
            $ipHash,
            ['status' => $input->status, 'locale' => $input->locale, 'content_key' => $input->contentKey],
        );
        if ($type === 'realisations' && $input->verifyForPublication) {
            $this->audit->record(
                $actorId,
                'case_study.verified',
                $type,
                $id,
                $requestId,
                $ipHash,
                ['content_hash' => $this->caseStudyPolicy->contentHash($input)],
            );
        }

        return new ContentSaveResult(true, $id, $validation);
    }
}

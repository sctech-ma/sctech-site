<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\DTO\ContentInput;
use SCTech\Validation\CaseStudyVerificationPolicy;

final class CaseStudyVerificationPolicyTest extends TestCase
{
    public function testEditorCannotPublishAnUnverifiedCaseStudy(): void
    {
        $input = $this->input('published');
        $errors = (new CaseStudyVerificationPolicy())->validate($input, 'editor', null)->errors();

        self::assertArrayHasKey('status', $errors);
        self::assertStringContainsString('administrateur', $errors['status'][0]);
    }

    public function testAdministratorMustDocumentAFirstPublicationApproval(): void
    {
        $input = $this->input('published');
        $errors = (new CaseStudyVerificationPolicy())->validate($input, 'admin', null)->errors();

        self::assertArrayHasKey('status', $errors);

        $approved = $this->input(
            'published',
            true,
            'Autorisation écrite du client reçue le 3 août 2026; périmètre: titre, résumé et contenu.',
        );
        self::assertTrue((new CaseStudyVerificationPolicy())->validate($approved, 'admin', null)->isValid());
    }

    public function testClaimBearingContentChangeInvalidatesTheStoredHash(): void
    {
        $policy = new CaseStudyVerificationPolicy();
        $input = $this->input('published');
        $existing = [
            'verified_at' => '2026-08-03 10:00:00.000000',
            'verified_by' => 7,
            'verification_notes' => 'Autorisation écrite vérifiée et archivée par un administrateur.',
            'verification_content_hash' => $policy->contentHash($input),
        ];

        self::assertTrue($policy->isCurrent($input, $existing));

        $changed = $this->input('published', false, '', 'Une affirmation modifiée');
        self::assertFalse($policy->isCurrent($changed, $existing));
        self::assertFalse($policy->validate($changed, 'admin', $existing)->isValid());
    }

    public function testPublishedCaseStudyRequiresAValidatedNarrativeBlock(): void
    {
        $input = new ContentInput(
            id: null,
            locale: 'fr',
            contentKey: 'case.empty-narrative',
            slug: 'empty-narrative',
            title: 'Réalisation sans narratif',
            summary: 'Résumé vérifié.',
            blocksJson: '{"version":1,"blocks":[]}',
            status: 'published',
            seoTitle: '',
            seoDescription: '',
            sortOrder: 0,
            expectedVersion: null,
            verificationNotes: 'Autorisation écrite vérifiée pour ce test de publication.',
            verifyForPublication: true,
        );

        $errors = (new CaseStudyVerificationPolicy())->validate($input, 'admin', null)->errors();
        self::assertArrayHasKey('blocks_json', $errors);
    }

    private function input(
        string $status,
        bool $approve = false,
        string $notes = '',
        string $summary = 'Une affirmation vérifiable.',
    ): ContentInput {
        return new ContentInput(
            id: null,
            locale: 'fr',
            contentKey: 'case.test-policy',
            slug: 'test-policy',
            title: 'Réalisation test',
            summary: $summary,
            blocksJson: '{"version":1,"blocks":[{"type":"paragraph","text":"Narratif vérifié."}]}',
            status: $status,
            seoTitle: '',
            seoDescription: '',
            sortOrder: 0,
            expectedVersion: null,
            verificationNotes: $notes,
            verifyForPublication: $approve,
        );
    }
}

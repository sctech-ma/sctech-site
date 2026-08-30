<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\DTO\ContactSubmission;
use SCTech\DTO\QuoteSubmission;
use SCTech\Validation\ContactValidator;
use SCTech\Validation\QuoteValidator;

final class FormSubmissionValidationTest extends TestCase
{
    public function testContactAcceptsOnlyThePublishedSubjectVocabulary(): void
    {
        $submission = ContactSubmission::fromArray([
            'full_name' => 'Nadia El Mansouri',
            'email' => 'Nadia@example.test',
            'organisation' => 'Organisation test',
            'phone' => '+212 600 000 000',
            'subject' => 'data-ai',
            'message' => 'Nous souhaitons structurer la gouvernance de nos données opérationnelles.',
            'consent_privacy' => '1',
        ]);

        self::assertSame('nadia@example.test', $submission->email);
        self::assertTrue((new ContactValidator())->validate($submission)->isValid());

        $invalid = ContactSubmission::fromArray(array_merge(
            $submission->safeFormValues(),
            ['subject' => 'custom-untrusted'],
        ));
        self::assertArrayHasKey('subject', (new ContactValidator())->validate($invalid)->errors());
    }

    public function testProjectInquiryAcceptsTheCanonicalRequiredFieldsAndOptionalContactDetails(): void
    {
        $submission = QuoteSubmission::fromArray([
            'full_name' => 'Nadia El Mansouri',
            'email' => 'nadia@example.test',
            'organisation' => 'Organisation test',
            'phone' => '',
            'project_type' => 'modernisation',
            'services_researched' => ['plateformes-investissement', 'integrations-financieres'],
            'timeline' => '3-6-mois',
            'project_context' => 'Nous modernisons un produit financier utilisé par plusieurs équipes métier.',
            'project_objective' => 'Rendre les règles configurables et chaque décision traçable.',
            'consent_privacy' => 'on',
        ]);

        self::assertSame('plateformes-investissement', $submission->serviceKey);
        self::assertTrue((new QuoteValidator())->validate($submission)->isValid());

        $invalid = QuoteSubmission::fromArray([
            ...$submission->safeFormValues(),
            'services_researched' => ['unknown'],
            'project_context' => '',
            'project_objective' => '',
        ]);
        $errors = (new QuoteValidator())->validate($invalid)->errors();
        self::assertArrayHasKey('services_researched', $errors);
        self::assertArrayNotHasKey('service_key', $errors);
        self::assertArrayHasKey('project_context', $errors);
        self::assertArrayHasKey('project_objective', $errors);
        self::assertArrayNotHasKey('phone', $errors);
        self::assertArrayNotHasKey('budget_range', $errors);
        self::assertArrayNotHasKey('preferred_contact_method', $errors);
    }

    public function testProjectInquiryRejectsMoreThanThreeDomainsAndMalformedUtf8(): void
    {
        $submission = QuoteSubmission::fromArray([
            'full_name' => 'Nadia El Mansouri',
            'email' => 'nadia@example.test',
            'organisation' => 'Organisation test',
            'project_type' => 'nouveau-produit',
            'services_researched' => [
                'plateformes-investissement',
                'portails-investisseurs',
                'workflows-conformite',
                'data-reporting',
            ],
            'timeline' => 'a-definir',
            'project_context' => "Contexte invalide \xC3\x28 qui doit être rejeté sans erreur serveur.",
            'project_objective' => 'Construire un workflow explicable.',
            'consent_privacy' => '1',
        ]);

        $errors = (new QuoteValidator())->validate($submission)->errors();

        self::assertArrayHasKey('services_researched', $errors);
        self::assertArrayHasKey('project_context', $errors);
        self::assertSame('Ce champ contient des caractères invalides.', $errors['project_context'][0]);
    }

    public function testUnicodeLengthsAndConsentAreValidatedServerSide(): void
    {
        $submission = ContactSubmission::fromArray([
            'full_name' => 'أ',
            'email' => 'invalid',
            'subject' => 'cloud',
            'message' => 'trop court',
            'consent_privacy' => '0',
        ]);
        $errors = (new ContactValidator())->validate($submission)->errors();

        self::assertArrayHasKey('full_name', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('message', $errors);
        self::assertArrayHasKey('consent_privacy', $errors);
    }
}

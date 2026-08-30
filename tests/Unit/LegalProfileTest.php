<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Core\Config;
use SCTech\Core\LegalProfile;

final class LegalProfileTest extends TestCase
{
    public function testCompleteApprovedProfileCanBePublished(): void
    {
        $profile = new LegalProfile($this->config());

        self::assertTrue($profile->isPublicReady());
        self::assertSame([], $profile->missingPublicRequirements());
        self::assertSame([], $profile->missingLogoRequirements());
        self::assertSame('RC 1 · ICE 2 · IF 3', $profile->data()['registration_ids']);
    }

    public function testIncompleteOrInvalidProfileRemainsBlocked(): void
    {
        $config = $this->config();
        $items = $config->all();
        $items['legal']['entity_name'] = '';
        $items['legal']['linkedin'] = 'http://example.test/sctech';
        $items['legal']['privacy_review_approved'] = false;
        $items['legal']['logo_rights_approved'] = false;
        $items['security']['lead_retention_days'] = 0;
        $items['security']['consent_policy_version'] = 'draft';
        $profile = new LegalProfile(new Config($items));

        self::assertFalse($profile->isPublicReady());
        self::assertContains('COMPANY_LEGAL_NAME', $profile->missingPublicRequirements());
        self::assertContains('COMPANY_LINKEDIN (URL HTTPS valide)', $profile->missingPublicRequirements());
        self::assertContains('PRIVACY_REVIEW_APPROVED', $profile->missingPublicRequirements());
        self::assertContains('LEAD_RETENTION_DAYS', $profile->missingPublicRequirements());
        self::assertContains('CONSENT_POLICY_VERSION', $profile->missingPublicRequirements());
        self::assertSame(['LOGO_RIGHTS_APPROVED'], $profile->missingLogoRequirements());
    }

    private function config(): Config
    {
        return new Config([
            'legal' => [
                'entity_name' => 'SCTECH SARL',
                'legal_form' => 'SARL',
                'full_address' => '1 rue Exemple, Casablanca, Maroc',
                'rc' => '1',
                'ice' => '2',
                'tax_id' => '3',
                'phone' => '+212 500 000 000',
                'linkedin' => 'https://www.linkedin.com/company/sctech',
                'publication_director' => 'Direction SCTECH',
                'hosting' => 'Hébergeur, adresse, contact',
                'retention_policy' => 'Les demandes sont supprimées après 365 jours.',
                'legal_review_approved' => true,
                'privacy_review_approved' => true,
                'cookie_review_approved' => true,
                'production_hosting_confirmed' => true,
                'logo_vector_master_received' => true,
                'logo_rights_approved' => true,
            ],
            'security' => [
                'lead_retention_days' => 365,
                'consent_policy_version' => '2026-08-03',
            ],
        ]);
    }
}

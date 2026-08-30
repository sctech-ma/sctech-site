<?php

declare(strict_types=1);

namespace SCTech\Core;

final class LegalProfile
{
    /** @var array<string, string> */
    private const REQUIRED_FIELDS = [
        'entity_name' => 'COMPANY_LEGAL_NAME',
        'legal_form' => 'COMPANY_LEGAL_FORM',
        'full_address' => 'COMPANY_ADDRESS',
        'rc' => 'COMPANY_RC',
        'ice' => 'COMPANY_ICE',
        'tax_id' => 'COMPANY_IF',
        'phone' => 'COMPANY_PHONE',
        'linkedin' => 'COMPANY_LINKEDIN',
        'publication_director' => 'COMPANY_PUBLICATION_DIRECTOR',
        'hosting' => 'COMPANY_HOSTING_DETAILS',
        'retention_policy' => 'PRIVACY_RETENTION_POLICY',
    ];

    /** @var array<string, string> */
    private const REQUIRED_APPROVALS = [
        'legal_review_approved' => 'LEGAL_REVIEW_APPROVED',
        'privacy_review_approved' => 'PRIVACY_REVIEW_APPROVED',
        'cookie_review_approved' => 'COOKIE_REVIEW_APPROVED',
        'production_hosting_confirmed' => 'PRODUCTION_HOSTING_CONFIRMED',
    ];

    public function __construct(private readonly Config $config)
    {
    }

    /** @return array<string, string> */
    public function data(): array
    {
        $data = [];
        foreach (array_keys(self::REQUIRED_FIELDS) as $field) {
            $data[$field] = trim($this->config->string('legal.' . $field));
        }
        $data['registration_ids'] = sprintf(
            'RC %s · ICE %s · IF %s',
            $data['rc'],
            $data['ice'],
            $data['tax_id']
        );
        $data['policy_version'] = trim($this->config->string('security.consent_policy_version', 'draft'));

        return $data;
    }

    /** @return list<string> */
    public function missingPublicRequirements(): array
    {
        $missing = [];
        foreach (self::REQUIRED_FIELDS as $field => $environmentKey) {
            $value = trim($this->config->string('legal.' . $field));
            if ($value === '') {
                $missing[] = $environmentKey;
            }
        }

        $linkedin = trim($this->config->string('legal.linkedin'));
        if (
            $linkedin !== ''
            && (
                filter_var($linkedin, FILTER_VALIDATE_URL) === false
                || !str_starts_with($linkedin, 'https://')
            )
        ) {
            $missing[] = 'COMPANY_LINKEDIN (URL HTTPS valide)';
        }

        if ($this->config->int('security.lead_retention_days', 0) <= 0) {
            $missing[] = 'LEAD_RETENTION_DAYS';
        }
        if (in_array($this->config->string('security.consent_policy_version', 'draft'), ['', 'draft'], true)) {
            $missing[] = 'CONSENT_POLICY_VERSION';
        }
        foreach (self::REQUIRED_APPROVALS as $field => $environmentKey) {
            if (!$this->config->bool('legal.' . $field, false)) {
                $missing[] = $environmentKey;
            }
        }

        return array_values(array_unique($missing));
    }

    public function isPublicReady(): bool
    {
        return $this->missingPublicRequirements() === [];
    }

    /** @return list<string> */
    public function missingLogoRequirements(): array
    {
        $missing = [];
        if (!$this->config->bool('legal.logo_vector_master_received', false)) {
            $missing[] = 'LOGO_VECTOR_MASTER_RECEIVED';
        }
        if (!$this->config->bool('legal.logo_rights_approved', false)) {
            $missing[] = 'LOGO_RIGHTS_APPROVED';
        }

        return $missing;
    }
}

<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class QuoteSubmission
{
    /**
     * @param list<string> $servicesResearched
     */
    public function __construct(
        public string $fullName,
        public string $email,
        public string $organisation,
        public string $phone,
        public string $projectType,
        public string $serviceKey,
        public array $servicesResearched,
        public string $budgetRange,
        public string $timeline,
        public string $preferredContactMethod,
        public string $projectSummary,
        public bool $consentPrivacy,
        public string $locale,
        public string $csrfToken,
        public string $timingToken,
        public string $idempotencyKey,
        public string $website,
        public string $projectContext = '',
        public string $projectObjective = '',
        public string $complianceRequirements = '',
        public string $integrationRequirements = '',
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $services = self::stringList($input['services_researched'] ?? []);
        $serviceKey = $services[0] ?? self::text($input, 'service_key');

        return new self(
            self::text($input, 'full_name'),
            mb_strtolower(self::text($input, 'email'), 'UTF-8'),
            self::text($input, 'organisation'),
            self::text($input, 'phone'),
            self::text($input, 'project_type'),
            $serviceKey,
            $services,
            self::text($input, 'budget_range'),
            self::text($input, 'timeline'),
            self::text($input, 'preferred_contact_method'),
            self::text($input, 'project_summary'),
            self::boolean($input['consent_privacy'] ?? false),
            self::text($input, 'locale') ?: 'fr',
            self::text($input, '_csrf') ?: self::text($input, '_token'),
            self::text($input, '_timing'),
            self::text($input, '_idempotency'),
            self::text($input, 'website'),
            self::text($input, 'project_context'),
            self::text($input, 'project_objective'),
            self::text($input, 'compliance_requirements'),
            self::text($input, 'integration_requirements'),
        );
    }

    /** @return array<string, scalar> */
    public function safeFormValues(): array
    {
        return [
            'full_name' => $this->fullName,
            'email' => $this->email,
            'organisation' => $this->organisation,
            'phone' => $this->phone,
            'project_type' => $this->projectType,
            'service_key' => $this->serviceKey,
            'services_researched' => implode(',', $this->servicesResearched),
            'budget_range' => $this->budgetRange,
            'timeline' => $this->timeline,
            'preferred_contact_method' => $this->preferredContactMethod,
            'project_summary' => $this->projectSummary,
            'project_context' => $this->projectContext,
            'project_objective' => $this->projectObjective,
            'compliance_requirements' => $this->complianceRequirements,
            'integration_requirements' => $this->integrationRequirements,
            'consent_privacy' => $this->consentPrivacy,
        ];
    }

    /** @param array<string, mixed> $input */
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function boolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(
                array_map('trim', explode(',', $value)),
                static fn (string $item): bool => $item !== '',
            );
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
                $value,
            ),
            static fn (string $item): bool => $item !== '',
        )));
    }
}

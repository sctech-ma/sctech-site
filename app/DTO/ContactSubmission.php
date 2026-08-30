<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class ContactSubmission
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $organisation,
        public string $phone,
        public string $subject,
        public string $message,
        public bool $consentPrivacy,
        public string $locale,
        public string $csrfToken,
        public string $timingToken,
        public string $idempotencyKey,
        public string $website,
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            self::text($input, 'full_name'),
            mb_strtolower(self::text($input, 'email'), 'UTF-8'),
            self::text($input, 'organisation'),
            self::text($input, 'phone'),
            self::text($input, 'subject'),
            self::text($input, 'message'),
            self::boolean($input['consent_privacy'] ?? false),
            self::text($input, 'locale') ?: 'fr',
            self::text($input, '_csrf') ?: self::text($input, '_token'),
            self::text($input, '_timing'),
            self::text($input, '_idempotency'),
            self::text($input, 'website'),
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
            'subject' => $this->subject,
            'message' => $this->message,
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
}

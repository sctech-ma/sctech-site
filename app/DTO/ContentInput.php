<?php

declare(strict_types=1);

namespace SCTech\DTO;

final readonly class ContentInput
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public ?int $id,
        public string $locale,
        public string $contentKey,
        public string $slug,
        public string $title,
        public string $summary,
        public string $blocksJson,
        public string $status,
        public string $seoTitle,
        public string $seoDescription,
        public int $sortOrder,
        public ?int $expectedVersion,
        public array $extra = [],
        public string $verificationNotes = '',
        public bool $verifyForPublication = false,
        public string $eyebrow = '',
        public string $problemText = '',
        public string $positioningText = '',
        public int $readingMinutes = 1,
        public string $categoryKey = '',
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            self::nullableInt($input['id'] ?? null),
            self::text($input, 'locale') ?: 'fr',
            self::text($input, 'content_key'),
            self::text($input, 'slug'),
            self::text($input, 'title'),
            self::text($input, 'summary'),
            self::text($input, 'blocks_json') ?: '{"version":1,"blocks":[]}',
            self::text($input, 'status') ?: 'draft',
            self::text($input, 'seo_title'),
            self::text($input, 'seo_description'),
            (int) ($input['sort_order'] ?? 0),
            self::nullableInt($input['version'] ?? null),
            is_array($input['extra'] ?? null) ? $input['extra'] : [],
            self::text($input, 'verification_notes'),
            self::boolean($input['verify_for_publication'] ?? false),
            self::text($input, 'eyebrow'),
            self::text($input, 'problem_text'),
            self::text($input, 'positioning_text'),
            self::integer($input['reading_minutes'] ?? null),
            self::text($input, 'category_key'),
        );
    }

    /** @param array<string, mixed> $input */
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private static function integer(mixed $value): int
    {
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return 0;
        }

        return (int) $value;
    }

    private static function boolean(mixed $value): bool
    {
        return is_scalar($value)
            && filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true;
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\DTO\ContentInput;
use SCTech\Validation\ContentValidator;
use SCTech\Validation\StructuredBlocksValidator;

final class StructuredContentTest extends TestCase
{
    public function testFixedBlockVocabularyIsAccepted(): void
    {
        $json = json_encode([
            'version' => 1,
            'blocks' => [
                ['type' => 'heading', 'level' => 2, 'text' => 'Un cap clair'],
                ['type' => 'paragraph', 'text' => 'Une architecture lisible soutient les arbitrages.'],
                ['type' => 'list', 'title' => 'Étapes', 'items' => ['Qualifier', 'Sécuriser', 'Décider']],
                ['type' => 'callout', 'title' => 'À retenir', 'text' => 'La preuve doit rester vérifiable.'],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        self::assertTrue((new StructuredBlocksValidator())->validateJson($json)->isValid());
    }

    public function testArbitraryHtmlAndPageBuilderBlocksAreRejected(): void
    {
        $validator = new StructuredBlocksValidator();
        $html = '{"version":1,"blocks":[{"type":"html","html":"<script>alert(1)</script>"}]}';
        $builder = '{"version":1,"blocks":[{"type":"component","template":"../../secret.php"}]}';

        self::assertFalse($validator->validateJson($html)->isValid());
        self::assertFalse($validator->validateJson($builder)->isValid());
    }

    public function testContentKeysSlugsStatusesAndSeoLimitsAreEnforced(): void
    {
        $input = new ContentInput(
            null,
            'fr',
            'Bad Key',
            '../unsafe',
            'Titre',
            'Résumé',
            '{"version":1,"blocks":[]}',
            'live',
            str_repeat('x', 191),
            str_repeat('x', 321),
            0,
            null,
        );
        $errors = (new ContentValidator(new StructuredBlocksValidator()))->validate($input)->errors();

        self::assertArrayHasKey('content_key', $errors);
        self::assertArrayHasKey('slug', $errors);
        self::assertArrayHasKey('status', $errors);
        self::assertArrayHasKey('seo_title', $errors);
        self::assertArrayHasKey('seo_description', $errors);
    }

    public function testArticleSummaryIsRequiredWithoutChangingOtherContentTypes(): void
    {
        $input = new ContentInput(
            null,
            'fr',
            'article.summary-test',
            'summary-test',
            'Article de test',
            '',
            '{"version":1,"blocks":[]}',
            'draft',
            '',
            '',
            0,
            null,
        );
        $validator = new ContentValidator(new StructuredBlocksValidator());

        self::assertArrayHasKey('summary', $validator->validate($input, 'articles')->errors());
        self::assertArrayNotHasKey('summary', $validator->validate($input, 'pages')->errors());
    }

    public function testArticleReadingMinutesAreParsedWithoutClampingAndValidatedAtBoundaries(): void
    {
        $base = [
            'locale' => 'fr',
            'content_key' => 'article.reading-time-test',
            'slug' => 'reading-time-test',
            'title' => 'Temps de lecture',
            'summary' => 'Résumé éditorial requis pour cet article de test.',
            'blocks_json' => '{"version":1,"blocks":[]}',
            'status' => 'draft',
            'category_key' => 'architecture-financiere',
        ];
        $validator = new ContentValidator(new StructuredBlocksValidator());

        foreach ([1, 60] as $minutes) {
            $input = ContentInput::fromArray($base + ['reading_minutes' => (string) $minutes]);
            self::assertSame($minutes, $input->readingMinutes);
            self::assertArrayNotHasKey('reading_minutes', $validator->validate($input, 'articles')->errors());
        }

        foreach ([0, -1, 61] as $minutes) {
            $input = ContentInput::fromArray($base + ['reading_minutes' => (string) $minutes]);
            self::assertSame($minutes, $input->readingMinutes);
            self::assertArrayHasKey('reading_minutes', $validator->validate($input, 'articles')->errors());
        }

        $malformed = ContentInput::fromArray($base + ['reading_minutes' => '4.5']);
        self::assertSame(0, $malformed->readingMinutes);
        self::assertArrayHasKey('reading_minutes', $validator->validate($malformed, 'articles')->errors());
    }
}

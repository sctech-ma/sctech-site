<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\Core\View;

final class PublicExperienceViewTest extends TestCase
{
    private View $view;

    /** @var array<string, mixed> */
    private array $language;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->view = new View($root . '/app/Views');
        $this->language = require $root . '/lang/fr.php';
    }

    public function testHomeKeepsEveryScenarioAvailableBeforeJavaScriptEnhancement(): void
    {
        $html = $this->view->render('pages/home', [
            'lang' => $this->language,
            'articles' => $this->language['insights'],
            'caseStudies' => [],
        ], 'layouts/public');

        self::assertSame(5, substr_count($html, '<article class="solution-story'));
        self::assertSame(3, substr_count($html, 'data-panel-step'));
        self::assertDoesNotMatchRegularExpression('/data-(?:panel-step|reveal)[^>]*\shidden(?:\s|>)/', $html);
        self::assertMatchesRegularExpression(
            '/<a class="button button--forest button--compact header-cta" href="\/demander-un-projet">/',
            $html,
        );
        self::assertStringContainsString('Interface conceptuelle', $html);
        self::assertStringContainsString(
            'SCTECH ne délivre ni avis religieux, ni certification, ni conseil en investissement.',
            $html,
        );
        self::assertStringContainsString('aria-label="Lire l’analyse :', $html);
    }

    public function testPublicLayoutEmitsCompleteSocialAndIconMetadata(): void
    {
        $html = $this->view->render('errors/404', [
            'lang' => $this->language,
            'canonical' => 'https://www.sctech.ma/404',
            'title' => 'Page introuvable — SCTECH',
            'description' => 'La page demandée est introuvable.',
        ], 'layouts/public');

        self::assertStringContainsString(
            '<meta property="og:image" content="https://www.sctech.ma/assets/images/social-preview.png">',
            $html,
        );
        self::assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        self::assertStringContainsString('<meta property="og:image:height" content="630">', $html);
        self::assertStringContainsString(
            '<meta name="twitter:image" content="https://www.sctech.ma/assets/images/social-preview.png">',
            $html,
        );
        self::assertStringContainsString('/assets/images/favicon-32x32.png', $html);
        self::assertStringContainsString('/assets/images/apple-touch-icon.png', $html);
    }

    public function testArticleUsesMachineReadableIsoPublicationDate(): void
    {
        $html = $this->view->render('pages/article', [
            'lang' => $this->language,
            'slug' => 'gouvernance-data-commencer-par-decisions',
            'article' => [
                'slug' => 'gouvernance-data-commencer-par-decisions',
                'title' => 'Gouvernance data : commencer par les décisions',
                'excerpt' => 'Un cadre utile.',
                'category' => 'Data & IA',
                'reading_time' => '4 min',
                'published_at' => '2026-08-03 16:09:48.293308',
                'published_iso' => '2026-08-03T16:09:48+00:00',
                'published_label' => '03.08.2026',
                'blocks' => [],
            ],
        ]);

        self::assertStringContainsString('datetime="2026-08-03T16:09:48+00:00"', $html);
        self::assertStringNotContainsString('datetime="2026-08-03 16:09:48.293308"', $html);
    }

    public function testGeneratedBrandAssetsHaveExpectedDimensions(): void
    {
        $images = dirname(__DIR__, 2) . '/public/assets/images/';

        self::assertSame([1200, 630], array_slice(getimagesize($images . 'social-preview.png'), 0, 2));
        self::assertSame([192, 192], array_slice(getimagesize($images . 'icon-192.png'), 0, 2));
        self::assertSame([512, 512], array_slice(getimagesize($images . 'icon-512.png'), 0, 2));
    }
}

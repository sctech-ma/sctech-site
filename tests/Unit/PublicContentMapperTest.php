<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Services\PublicContentMapper;
use SCTech\Validation\StructuredBlocksValidator;

final class PublicContentMapperTest extends TestCase
{
    public function testPublishedExpertiseOverridesOnlyValidatedEditableFields(): void
    {
        $mapper = new PublicContentMapper(new StructuredBlocksValidator());
        $fallback = [
            'name' => 'Nom initial',
            'short' => 'Résumé initial',
            'lead' => 'Introduction initiale',
            'problems' => ['Problème initial'],
            'capabilities' => ['Capacité initiale'],
            'use_cases' => ['Usage conservé'],
            'related' => ['cloud-devsecops'],
        ];
        $row = [
            'slug' => 'data-ia',
            'title' => 'Data publiée',
            'summary' => 'Résumé publié',
            'eyebrow' => 'Signal publié',
            'problem_text' => 'Problème publié',
            'positioning_text' => 'Positionnement publié',
            'seo_title' => 'SEO publié',
            'seo_description' => 'Description SEO',
            'blocks_json' => '{"version":1,"blocks":[{"type":"list","title":"Capacités","items":["Gouvernance","Qualité"]}]}',
        ];

        $result = $mapper->expertise($fallback, $row, 1);

        self::assertSame('Data publiée', $result['name']);
        self::assertSame(['Problème publié'], $result['problems']);
        self::assertSame(['Gouvernance', 'Qualité'], $result['capabilities']);
        self::assertSame(['Usage conservé'], $result['use_cases']);
        self::assertSame('/expertises/data-ia', $result['href']);
    }

    public function testInvalidStructuredContentIsNeverRenderedOrUsedAsCapabilities(): void
    {
        $mapper = new PublicContentMapper(new StructuredBlocksValidator());
        $row = ['blocks_json' => '{"version":1,"blocks":[{"type":"html","text":"<script>"}]}'];

        self::assertSame([], $mapper->page($row)['blocks']);
        self::assertSame(
            ['Capacité sûre'],
            $mapper->expertise(['capabilities' => ['Capacité sûre']], $row + ['slug' => 'test'], 1)['capabilities']
        );
    }
}

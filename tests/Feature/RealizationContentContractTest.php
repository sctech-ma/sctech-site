<?php

declare(strict_types=1);

namespace SCTech\Tests\Feature;

use PHPUnit\Framework\TestCase;
use SCTech\Services\PublicContentMapper;
use SCTech\Validation\StructuredBlocksValidator;

final class RealizationContentContractTest extends TestCase
{
    public function testPublishedNarrativeUsesValidatedEscapedBlocksWithoutPlaceholderClaims(): void
    {
        $mapper = new PublicContentMapper(new StructuredBlocksValidator());
        $caseStudy = [
            'title' => 'Cas vérifié',
            'slug' => 'cas-verifie',
            'summary' => 'Un résumé autorisé.',
            'blocks' => $mapper->blocks(
                '{"version":1,"blocks":[{"type":"paragraph","text":"Preuve <script>alert(1)</script>"}]}',
            ),
        ];

        ob_start();
        try {
            require dirname(__DIR__, 2) . '/app/Views/pages/realization.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString('Preuve &lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('non renseign', mb_strtolower($html, 'UTF-8'));
    }
}

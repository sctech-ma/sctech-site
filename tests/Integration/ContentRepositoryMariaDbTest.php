<?php

declare(strict_types=1);

namespace SCTech\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use SCTech\DTO\CaseStudyVerificationInput;
use SCTech\DTO\ContentInput;
use SCTech\Repositories\CaseStudyPublicationException;
use SCTech\Repositories\ContentRepository;
use SCTech\Repositories\OptimisticLockException;

final class ContentRepositoryMariaDbTest extends TestCase
{
    public function testInsertPublishLookupAndOptimisticLockAgainstMariaDb(): void
    {
        $dsn = getenv('TEST_DB_DSN');
        if (!is_string($dsn) || $dsn === '') {
            self::markTestSkipped('Set TEST_DB_DSN to an already migrated disposable MariaDB database.');
        }
        $pdo = new PDO($dsn, (string) getenv('TEST_DB_USER'), (string) getenv('TEST_DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->beginTransaction();
        try {
            $repository = new ContentRepository($pdo);
            $key = 'test.integration.' . bin2hex(random_bytes(6));
            $slug = 'integration-' . bin2hex(random_bytes(6));
            $id = $repository->save('pages', new ContentInput(
                null,
                'fr',
                $key,
                $slug,
                'Page de test',
                'Contenu non public créé dans une transaction de test.',
                '{"version":1,"blocks":[]}',
                'published',
                '',
                '',
                0,
                null,
            ));

            self::assertNotNull($repository->findPublishedBySlug('pages', 'fr', $slug));
            $updated = new ContentInput(
                $id,
                'fr',
                $key,
                $slug,
                'Page de test mise à jour',
                'Contenu de test.',
                '{"version":1,"blocks":[]}',
                'draft',
                '',
                '',
                0,
                1,
            );
            self::assertSame($id, $repository->save('pages', $updated));
            self::expectException(OptimisticLockException::class);
            $repository->save('pages', $updated);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    public function testPublicQueriesExcludeFutureAndUnverifiedRecords(): void
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $repository = new ContentRepository($pdo);
            $suffix = bin2hex(random_bytes(6));
            $articleSlug = 'future-article-' . $suffix;
            $caseSlug = 'future-case-' . $suffix;
            $unverifiedSlug = 'unverified-case-' . $suffix;

            $pdo->prepare(<<<'SQL'
                INSERT INTO users (email, password_hash, display_name, role, status)
                VALUES (:email, :password_hash, 'Test publication', 'admin', 'active')
                SQL)->execute([
                    'email' => 'publication-' . $suffix . '@example.test',
                    'password_hash' => password_hash('Test-only-password!42', PASSWORD_DEFAULT),
                ]);
            $administratorId = (int) $pdo->lastInsertId();

            $pdo->prepare(<<<'SQL'
                INSERT INTO articles
                    (locale, content_key, slug, title, summary, blocks_json, status, published_at)
                VALUES
                    ('fr', :content_key, :slug, 'Article futur', 'Résumé requis.',
                     '{"version":1,"blocks":[]}', 'published',
                     UTC_TIMESTAMP(6) + INTERVAL 1 DAY)
                SQL)->execute([
                    'content_key' => 'test.future-article.' . $suffix,
                    'slug' => $articleSlug,
                ]);

            $caseStatement = $pdo->prepare(<<<'SQL'
                INSERT INTO case_studies
                    (locale, content_key, slug, title, summary, blocks_json, status, published_at,
                     verification_notes, verified_at, verified_by, verification_content_hash)
                VALUES
                    ('fr', :content_key, :slug, :title, 'Résumé vérifié.',
                     '{"version":1,"blocks":[]}', 'published', :published_at,
                     :verification_notes, UTC_TIMESTAMP(6), :verified_by, :verification_content_hash)
                SQL);
            $caseStatement->execute([
                'content_key' => 'test.future-case.' . $suffix,
                'slug' => $caseSlug,
                'title' => 'Réalisation future',
                'published_at' => gmdate('Y-m-d H:i:s', time() + 86400),
                'verification_notes' => 'Autorisation écrite vérifiée pour ce test automatisé.',
                'verified_by' => $administratorId,
                'verification_content_hash' => str_repeat('a', 64),
            ]);
            $caseStatement->execute([
                'content_key' => 'test.unverified-case.' . $suffix,
                'slug' => $unverifiedSlug,
                'title' => 'Réalisation non vérifiée',
                'published_at' => gmdate('Y-m-d H:i:s', time() - 60),
                'verification_notes' => null,
                'verified_by' => null,
                'verification_content_hash' => null,
            ]);

            self::assertNull($repository->findPublishedBySlug('articles', 'fr', $articleSlug));
            self::assertNull($repository->findPublishedBySlug('realisations', 'fr', $caseSlug));
            self::assertNull($repository->findPublishedBySlug('realisations', 'fr', $unverifiedSlug));

            $articleSlugs = array_column($repository->paginate('articles', 'fr', 'published'), 'slug');
            $caseSlugs = array_column($repository->paginate('realisations', 'fr', 'published'), 'slug');
            self::assertNotContains($articleSlug, $articleSlugs);
            self::assertNotContains($caseSlug, $caseSlugs);
            self::assertNotContains($unverifiedSlug, $caseSlugs);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    public function testClaimEditClearsDurableCaseStudyVerification(): void
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $repository = new ContentRepository($pdo);
            $suffix = bin2hex(random_bytes(6));
            $pdo->prepare(<<<'SQL'
                INSERT INTO users (email, password_hash, display_name, role, status)
                VALUES (:email, :password_hash, 'Test validation', 'admin', 'active')
                SQL)->execute([
                    'email' => 'verification-' . $suffix . '@example.test',
                    'password_hash' => password_hash('Test-only-password!42', PASSWORD_DEFAULT),
                ]);
            $administratorId = (int) $pdo->lastInsertId();
            $key = 'test.verified-case.' . $suffix;
            $slug = 'verified-case-' . $suffix;
            $notes = 'Autorisation écrite reçue et vérifiée pour le contenu de ce test.';
            $id = $repository->save(
                'realisations',
                $this->caseStudyInput(null, null, $key, $slug, 'Résumé initial.', 'published', true, $notes),
                new CaseStudyVerificationInput($administratorId, 'admin', true, $notes),
            );

            self::assertNotNull($repository->findPublishedBySlug('realisations', 'fr', $slug));
            $verified = $repository->find('realisations', $id);
            self::assertNotNull($verified['verified_at'] ?? null);
            self::assertSame($administratorId, (int) ($verified['verified_by'] ?? 0));

            $repository->save(
                'realisations',
                $this->caseStudyInput($id, 1, $key, $slug, 'Résumé modifié.', 'draft'),
                new CaseStudyVerificationInput($administratorId, 'admin', false, ''),
            );
            $changed = $repository->find('realisations', $id);
            self::assertNull($changed['verified_at'] ?? null);
            self::assertNull($changed['verified_by'] ?? null);
            self::assertNull($changed['verification_content_hash'] ?? null);
            self::assertNull($changed['verification_notes'] ?? null);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    public function testRepositoryRejectsEditorPublicationAsDefenseInDepth(): void
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $repository = new ContentRepository($pdo);
            $suffix = bin2hex(random_bytes(6));

            $this->expectException(CaseStudyPublicationException::class);
            $repository->save(
                'realisations',
                $this->caseStudyInput(
                    null,
                    null,
                    'test.editor-case.' . $suffix,
                    'editor-case-' . $suffix,
                    'Résumé non vérifié.',
                    'published',
                ),
                new CaseStudyVerificationInput(42, 'editor', false, ''),
            );
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    public function testSolutionPrefixQueriesNeverExposeLegacyGeneralistServices(): void
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $repository = new ContentRepository($pdo);
            $suffix = bin2hex(random_bytes(6));
            $statement = $pdo->prepare(<<<'SQL'
                INSERT INTO services
                    (locale, content_key, slug, title, summary, blocks_json, status, published_at)
                VALUES
                    ('fr', :legacy_key, :legacy_slug, 'Ancienne expertise', 'Contenu généraliste.',
                     '{"version":1,"blocks":[]}', 'published', UTC_TIMESTAMP(6)),
                    ('fr', :solution_key, :solution_slug, 'Solution financière', 'Contenu financier.',
                     '{"version":1,"blocks":[]}', 'published', UTC_TIMESTAMP(6))
                SQL);
            $statement->execute([
                'legacy_key' => 'expertise.legacy-' . $suffix,
                'legacy_slug' => 'legacy-' . $suffix,
                'solution_key' => 'solution.test-' . $suffix,
                'solution_slug' => 'solution-' . $suffix,
            ]);

            $rows = $repository->publishedByKeyPrefix('expertises', 'fr', 'solution.', 100);
            $keys = array_column($rows, 'content_key');
            self::assertContains('solution.test-' . $suffix, $keys);
            self::assertNotContains('expertise.legacy-' . $suffix, $keys);
            self::assertNull($repository->findPublishedBySlugAndKeyPrefix(
                'expertises',
                'fr',
                'legacy-' . $suffix,
                'solution.',
            ));
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    private function connection(): PDO
    {
        $dsn = getenv('TEST_DB_DSN');
        if (!is_string($dsn) || $dsn === '') {
            self::markTestSkipped('Set TEST_DB_DSN to an already migrated disposable MariaDB database.');
        }

        return new PDO($dsn, (string) getenv('TEST_DB_USER'), (string) getenv('TEST_DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private function caseStudyInput(
        ?int $id,
        ?int $version,
        string $key,
        string $slug,
        string $summary,
        string $status,
        bool $approve = false,
        string $verificationNotes = '',
    ): ContentInput {
        return new ContentInput(
            $id,
            'fr',
            $key,
            $slug,
            'Réalisation de test',
            $summary,
            '{"version":1,"blocks":[{"type":"paragraph","text":"Narratif vérifié."}]}',
            $status,
            '',
            '',
            0,
            $version,
            [],
            $verificationNotes,
            $approve,
        );
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use PDO;
use SCTech\DTO\CaseStudyVerificationInput;
use SCTech\DTO\ContentInput;
use SCTech\Validation\CaseStudyVerificationPolicy;

final class ContentRepository
{
    /** @var array<string, string> */
    private const TABLES = [
        'pages' => 'pages',
        'expertises' => 'services',
        'secteurs' => 'sectors',
        'realisations' => 'case_studies',
        'articles' => 'articles',
    ];

    private readonly CaseStudyVerificationPolicy $caseStudyPolicy;

    public function __construct(
        private readonly PDO $pdo,
        ?CaseStudyVerificationPolicy $caseStudyPolicy = null,
    ) {
        $this->caseStudyPolicy = $caseStudyPolicy ?? new CaseStudyVerificationPolicy();
    }

    /** @return list<array<string, mixed>> */
    public function paginate(string $type, string $locale = 'fr', string $status = '', int $page = 1, int $perPage = 30): array
    {
        $table = $this->table($type);
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$table} WHERE locale = :locale";
        $params = ['locale' => $locale];
        if (in_array($status, ['draft', 'published', 'archived'], true)) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
            if ($status === 'published') {
                $sql .= ' AND published_at IS NOT NULL AND published_at <= UTC_TIMESTAMP(6)';
                $sql .= $this->verifiedPublicationClause($type);
            }
        }
        $sql .= ' ORDER BY sort_order ASC, updated_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $records = [];
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        return $records;
    }

    /** @return array<string, mixed>|null */
    public function find(string $type, int $id): ?array
    {
        $table = $this->table($type);
        $statement = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $type, string $locale, string $slug): ?array
    {
        $table = $this->table($type);
        $verifiedPublicationClause = $this->verifiedPublicationClause($type);
        $statement = $this->pdo->prepare(<<<SQL
            SELECT * FROM {$table}
            WHERE locale = :locale AND slug = :slug AND status = 'published'
              AND published_at IS NOT NULL AND published_at <= UTC_TIMESTAMP(6)
              {$verifiedPublicationClause}
            LIMIT 1
            SQL);
        $statement->execute(['locale' => $locale, 'slug' => $slug]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findPublishedBySlugAndKeyPrefix(
        string $type,
        string $locale,
        string $slug,
        string $contentKeyPrefix,
    ): ?array {
        $table = $this->table($type);
        $verifiedPublicationClause = $this->verifiedPublicationClause($type);
        $statement = $this->pdo->prepare(<<<SQL
            SELECT * FROM {$table}
            WHERE locale = :locale AND slug = :slug AND content_key LIKE :content_key_prefix
              AND status = 'published' AND published_at IS NOT NULL
              AND published_at <= UTC_TIMESTAMP(6)
              {$verifiedPublicationClause}
            LIMIT 1
            SQL);
        $statement->execute([
            'locale' => $locale,
            'slug' => $slug,
            'content_key_prefix' => $this->likePrefix($contentKeyPrefix),
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function publishedByKeyPrefix(
        string $type,
        string $locale,
        string $contentKeyPrefix,
        int $limit = 100,
    ): array {
        $table = $this->table($type);
        $limit = max(1, min(100, $limit));
        $verifiedPublicationClause = $this->verifiedPublicationClause($type);
        $statement = $this->pdo->prepare(<<<SQL
            SELECT * FROM {$table}
            WHERE locale = :locale AND content_key LIKE :content_key_prefix
              AND status = 'published' AND published_at IS NOT NULL
              AND published_at <= UTC_TIMESTAMP(6)
              {$verifiedPublicationClause}
            ORDER BY sort_order ASC, updated_at DESC
            LIMIT {$limit}
            SQL);
        $statement->execute([
            'locale' => $locale,
            'content_key_prefix' => $this->likePrefix($contentKeyPrefix),
        ]);

        return array_values(array_filter(
            $statement->fetchAll(PDO::FETCH_ASSOC),
            'is_array',
        ));
    }

    public function save(
        string $type,
        ContentInput $input,
        ?CaseStudyVerificationInput $verification = null,
    ): int {
        if ($type === 'articles' && $input->summary === '') {
            throw new \InvalidArgumentException('An article summary is required.');
        }
        if ($type === 'realisations') {
            if ($verification === null) {
                throw new CaseStudyPublicationException(
                    'Une identité administrateur est requise pour enregistrer une réalisation.',
                );
            }

            return $this->saveCaseStudy($input, $verification);
        }

        if ($type === 'expertises') {
            return $this->saveService($input);
        }
        if ($type === 'articles') {
            return $this->saveArticle($input);
        }

        $table = $this->table($type);
        if ($input->id === null) {
            $statement = $this->pdo->prepare(<<<SQL
                INSERT INTO {$table}
                    (locale, content_key, slug, title, eyebrow, summary, blocks_json, status,
                     seo_title, seo_description, sort_order, published_at)
                VALUES
                    (:locale, :content_key, :slug, :title, :eyebrow, :summary, :blocks_json, :status,
                     :seo_title, :seo_description, :sort_order,
                     CASE WHEN :publication_status = 'published' THEN UTC_TIMESTAMP(6) ELSE NULL END)
                SQL);
            $statement->execute($this->parameters($input) + $this->editorialParameters($input));

            return (int) $this->pdo->lastInsertId();
        }

        if ($input->expectedVersion === null) {
            throw new \InvalidArgumentException('An expected version is required for updates.');
        }
        $statement = $this->pdo->prepare(<<<SQL
            UPDATE {$table}
            SET locale = :locale, content_key = :content_key, slug = :slug, title = :title,
                eyebrow = :eyebrow, summary = :summary, blocks_json = :blocks_json, status = :status,
                seo_title = :seo_title, seo_description = :seo_description, sort_order = :sort_order,
                published_at = CASE
                    WHEN :publication_status = 'published' THEN COALESCE(published_at, UTC_TIMESTAMP(6))
                    ELSE NULL
                END,
                version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $params = $this->parameters($input) + $this->editorialParameters($input);
        $params['id'] = $input->id;
        $params['version'] = $input->expectedVersion;
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new OptimisticLockException('Le contenu a été modifié dans une autre session. Rechargez la page.');
        }

        return $input->id;
    }

    private function saveService(ContentInput $input): int
    {
        if ($input->id === null) {
            $statement = $this->pdo->prepare(<<<'SQL'
                INSERT INTO services
                    (locale, content_key, slug, title, eyebrow, summary, problem_text, positioning_text,
                     blocks_json, status, seo_title, seo_description, sort_order, published_at)
                VALUES
                    (:locale, :content_key, :slug, :title, :eyebrow, :summary, :problem_text, :positioning_text,
                     :blocks_json, :status, :seo_title, :seo_description, :sort_order,
                     CASE WHEN :publication_status = 'published' THEN UTC_TIMESTAMP(6) ELSE NULL END)
                SQL);
            $statement->execute($this->parameters($input) + $this->editorialParameters($input) + [
                'problem_text' => $input->problemText !== '' ? $input->problemText : null,
                'positioning_text' => $input->positioningText !== '' ? $input->positioningText : null,
            ]);

            return (int) $this->pdo->lastInsertId();
        }

        $this->requireExpectedVersion($input);
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE services
            SET locale = :locale, content_key = :content_key, slug = :slug, title = :title,
                eyebrow = :eyebrow, summary = :summary, problem_text = :problem_text,
                positioning_text = :positioning_text, blocks_json = :blocks_json, status = :status,
                seo_title = :seo_title, seo_description = :seo_description, sort_order = :sort_order,
                published_at = CASE
                    WHEN :publication_status = 'published' THEN COALESCE(published_at, UTC_TIMESTAMP(6))
                    ELSE NULL
                END,
                version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $params = $this->parameters($input) + $this->editorialParameters($input) + [
            'problem_text' => $input->problemText !== '' ? $input->problemText : null,
            'positioning_text' => $input->positioningText !== '' ? $input->positioningText : null,
            'id' => $input->id,
            'version' => $input->expectedVersion,
        ];
        $statement->execute($params);
        $this->assertUpdated($statement);

        return (int) $input->id;
    }

    private function saveArticle(ContentInput $input): int
    {
        if ($input->id === null) {
            $statement = $this->pdo->prepare(<<<'SQL'
                INSERT INTO articles
                    (locale, content_key, slug, title, eyebrow, category_key, summary, blocks_json, status,
                     seo_title, seo_description, reading_minutes, sort_order, published_at)
                VALUES
                    (:locale, :content_key, :slug, :title, :eyebrow, :category_key, :summary, :blocks_json, :status,
                     :seo_title, :seo_description, :reading_minutes, :sort_order,
                     CASE WHEN :publication_status = 'published' THEN UTC_TIMESTAMP(6) ELSE NULL END)
                SQL);
            $statement->execute($this->parameters($input) + $this->editorialParameters($input) + [
                'category_key' => $input->categoryKey !== '' ? $input->categoryKey : null,
                'reading_minutes' => $input->readingMinutes,
            ]);

            return (int) $this->pdo->lastInsertId();
        }

        $this->requireExpectedVersion($input);
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE articles
            SET locale = :locale, content_key = :content_key, slug = :slug, title = :title,
                eyebrow = :eyebrow, category_key = :category_key, summary = :summary,
                blocks_json = :blocks_json, status = :status, seo_title = :seo_title,
                seo_description = :seo_description, reading_minutes = :reading_minutes,
                sort_order = :sort_order,
                published_at = CASE
                    WHEN :publication_status = 'published' THEN COALESCE(published_at, UTC_TIMESTAMP(6))
                    ELSE NULL
                END,
                version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $params = $this->parameters($input) + $this->editorialParameters($input) + [
            'category_key' => $input->categoryKey !== '' ? $input->categoryKey : null,
            'reading_minutes' => $input->readingMinutes,
            'id' => $input->id,
            'version' => $input->expectedVersion,
        ];
        $statement->execute($params);
        $this->assertUpdated($statement);

        return (int) $input->id;
    }

    public function countPublished(string $type, string $locale = 'fr'): int
    {
        $table = $this->table($type);
        $verifiedPublicationClause = $this->verifiedPublicationClause($type);
        $statement = $this->pdo->prepare(<<<SQL
            SELECT COUNT(*) FROM {$table}
            WHERE locale = :locale AND status = 'published'
              AND published_at IS NOT NULL AND published_at <= UTC_TIMESTAMP(6)
              {$verifiedPublicationClause}
            SQL);
        $statement->execute(['locale' => $locale]);

        return (int) $statement->fetchColumn();
    }

    private function saveCaseStudy(ContentInput $input, CaseStudyVerificationInput $verification): int
    {
        $existing = null;
        if ($input->id !== null) {
            if ($input->expectedVersion === null) {
                throw new \InvalidArgumentException('An expected version is required for updates.');
            }
            $existing = $this->find('realisations', $input->id);
            if ($existing === null || (int) ($existing['version'] ?? 0) !== $input->expectedVersion) {
                throw new OptimisticLockException(
                    'Le contenu a été modifié dans une autre session. Rechargez la page.',
                );
            }
        }

        $policyValidation = $this->caseStudyPolicy->validate($input, $verification->actorRole, $existing);
        if (!$policyValidation->isValid()) {
            throw new CaseStudyPublicationException(
                $policyValidation->first('status')
                    ?? $policyValidation->first('verify_for_publication')
                    ?? $policyValidation->first('verification_notes')
                    ?? 'La réalisation ne peut pas être publiée sans validation.',
            );
        }

        $isCurrent = $this->caseStudyPolicy->isCurrent($input, $existing);
        $isApproved = $verification->approve && $verification->isAdministrator();
        $verificationParameters = [
            'verification_notes' => $isApproved
                ? $verification->notes
                : ($isCurrent ? $existing['verification_notes'] : null),
            'verified_at' => $isCurrent ? $existing['verified_at'] : null,
            'verified_by' => $isApproved
                ? $verification->actorId
                : ($isCurrent ? $existing['verified_by'] : null),
            'verification_content_hash' => $isApproved
                ? $this->caseStudyPolicy->contentHash($input)
                : ($isCurrent ? $existing['verification_content_hash'] : null),
            'verification_approved' => $isApproved ? 1 : 0,
        ];

        if ($input->id === null) {
            $statement = $this->pdo->prepare(<<<'SQL'
                INSERT INTO case_studies
                    (locale, content_key, slug, title, summary, blocks_json, status,
                     seo_title, seo_description, sort_order, published_at, verification_notes,
                     verified_at, verified_by, verification_content_hash)
                VALUES
                    (:locale, :content_key, :slug, :title, :summary, :blocks_json, :status,
                     :seo_title, :seo_description, :sort_order,
                     CASE WHEN :publication_status = 'published' THEN UTC_TIMESTAMP(6) ELSE NULL END,
                     :verification_notes,
                     CASE WHEN :verification_approved = 1 THEN UTC_TIMESTAMP(6) ELSE NULL END,
                     :verified_by, :verification_content_hash)
                SQL);
            $insertParameters = $this->parameters($input) + $verificationParameters;
            unset($insertParameters['verified_at']);
            $statement->execute($insertParameters);

            return (int) $this->pdo->lastInsertId();
        }

        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE case_studies
            SET locale = :locale, content_key = :content_key, slug = :slug, title = :title,
                summary = :summary, blocks_json = :blocks_json, status = :status,
                seo_title = :seo_title, seo_description = :seo_description, sort_order = :sort_order,
                published_at = CASE
                    WHEN :publication_status = 'published' THEN COALESCE(published_at, UTC_TIMESTAMP(6))
                    ELSE NULL
                END,
                verification_notes = :verification_notes,
                verified_at = CASE
                    WHEN :verification_approved = 1 THEN UTC_TIMESTAMP(6)
                    ELSE :verified_at
                END,
                verified_by = :verified_by,
                verification_content_hash = :verification_content_hash,
                version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $params = $this->parameters($input) + $verificationParameters;
        $params['id'] = $input->id;
        $params['version'] = $input->expectedVersion;
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new OptimisticLockException(
                'Le contenu a été modifié dans une autre session. Rechargez la page.',
            );
        }

        return $input->id;
    }

    /** @return array<string, scalar|null> */
    private function parameters(ContentInput $input): array
    {
        return [
            'locale' => $input->locale,
            'content_key' => $input->contentKey,
            'slug' => $input->slug,
            'title' => $input->title,
            'summary' => $input->summary !== '' ? $input->summary : null,
            'blocks_json' => $input->blocksJson,
            'status' => $input->status,
            'seo_title' => $input->seoTitle !== '' ? $input->seoTitle : null,
            'seo_description' => $input->seoDescription !== '' ? $input->seoDescription : null,
            'sort_order' => $input->sortOrder,
            'publication_status' => $input->status,
        ];
    }

    /** @return array{eyebrow: string|null} */
    private function editorialParameters(ContentInput $input): array
    {
        return ['eyebrow' => $input->eyebrow !== '' ? $input->eyebrow : null];
    }

    private function requireExpectedVersion(ContentInput $input): void
    {
        if ($input->expectedVersion === null) {
            throw new \InvalidArgumentException('An expected version is required for updates.');
        }
    }

    private function assertUpdated(\PDOStatement $statement): void
    {
        if ($statement->rowCount() !== 1) {
            throw new OptimisticLockException(
                'Le contenu a été modifié dans une autre session. Rechargez la page.',
            );
        }
    }

    private function likePrefix(string $prefix): string
    {
        if (preg_match('/^[a-z0-9][a-z0-9.-]{1,118}\.$/', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid content key prefix.');
        }

        return $prefix . '%';
    }

    private function table(string $type): string
    {
        return self::TABLES[$type] ?? throw new \InvalidArgumentException('Unsupported content type.');
    }

    private function verifiedPublicationClause(string $type): string
    {
        if ($type !== 'realisations') {
            return '';
        }

        return ' AND verified_at IS NOT NULL'
            . ' AND verified_by IS NOT NULL'
            . ' AND verification_content_hash IS NOT NULL'
            . ' AND CHAR_LENGTH(verification_content_hash) = 64'
            . ' AND verification_notes IS NOT NULL'
            . ' AND CHAR_LENGTH(TRIM(verification_notes)) >= 20';
    }
}

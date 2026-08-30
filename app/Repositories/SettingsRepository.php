<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function allForAdmin(string $locale = 'fr'): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT id, locale, content_key, value_json, status, is_sensitive, version, updated_at
            FROM site_settings WHERE locale = :locale ORDER BY content_key
            SQL);
        $statement->execute(['locale' => $locale]);

        $records = [];
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        return $records;
    }

    /** @return array<string, mixed>|null */
    public function find(string $locale, string $contentKey): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT * FROM site_settings WHERE locale = :locale AND content_key = :content_key LIMIT 1
            SQL);
        $statement->execute(['locale' => $locale, 'content_key' => $contentKey]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function save(string $locale, string $contentKey, string $valueJson, string $status, bool $sensitive, ?int $version): int
    {
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            throw new \InvalidArgumentException('Unsupported status.');
        }
        $existing = $this->find($locale, $contentKey);
        if ($existing === null) {
            $statement = $this->pdo->prepare(<<<'SQL'
                INSERT INTO site_settings (locale, content_key, value_json, status, is_sensitive)
                VALUES (:locale, :content_key, :value_json, :status, :is_sensitive)
                SQL);
            $statement->execute([
                'locale' => $locale,
                'content_key' => $contentKey,
                'value_json' => $valueJson,
                'status' => $status,
                'is_sensitive' => $sensitive ? 1 : 0,
            ]);

            return (int) $this->pdo->lastInsertId();
        }
        if ($version === null) {
            throw new \InvalidArgumentException('An expected version is required for updates.');
        }
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE site_settings
            SET value_json = :value_json, status = :status, is_sensitive = :is_sensitive, version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $statement->execute([
            'value_json' => $valueJson,
            'status' => $status,
            'is_sensitive' => $sensitive ? 1 : 0,
            'id' => $existing['id'],
            'version' => $version,
        ]);
        if ($statement->rowCount() !== 1) {
            throw new OptimisticLockException('Le paramètre a été modifié dans une autre session.');
        }

        return (int) $existing['id'];
    }
}

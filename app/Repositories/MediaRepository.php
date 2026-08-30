<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use PDO;

final class MediaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(string $locale = 'fr'): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM media WHERE locale = :locale ORDER BY created_at DESC');
        $statement->execute(['locale' => $locale]);

        $records = [];
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        return $records;
    }

    /** @param array<string, int|string|null> $media */
    public function create(array $media): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO media
                (locale, content_key, disk, path, original_name, mime_type, byte_size,
                 width, height, alt_text, caption, status, created_by)
            VALUES
                (:locale, :content_key, 'public', :path, :original_name, :mime_type, :byte_size,
                 :width, :height, :alt_text, :caption, 'draft', :created_by)
            SQL);
        $statement->execute($media);

        return (int) $this->pdo->lastInsertId();
    }
}

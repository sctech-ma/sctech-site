<?php

declare(strict_types=1);

namespace SCTech\Core;

use PDO;
use Throwable;

final class MigrationAudit
{
    /** @return list<string> */
    public function failures(PDO $pdo, string $directory): array
    {
        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files, SORT_STRING);
        if ($files === []) {
            return ['Aucune migration source trouvée'];
        }

        try {
            $statement = $pdo->query('SELECT version, checksum FROM migrations');
            if ($statement === false) {
                return ['Table de suivi des migrations accessible'];
            }
            $stored = [];
            while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                if (is_array($row) && isset($row['version'], $row['checksum'])) {
                    $stored[(string) $row['version']] = (string) $row['checksum'];
                }
            }
        } catch (Throwable) {
            return ['Table de suivi des migrations accessible'];
        }

        $failures = [];
        $expectedVersions = [];
        foreach ($files as $file) {
            $version = pathinfo($file, PATHINFO_FILENAME);
            $expectedVersions[$version] = true;
            $checksum = hash_file('sha256', $file);
            if (!isset($stored[$version])) {
                $failures[] = "Migration appliquée {$version}";
                continue;
            }
            if (!is_string($checksum) || !hash_equals($stored[$version], $checksum)) {
                $failures[] = "Checksum de migration valide {$version}";
            }
        }
        foreach (array_keys($stored) as $version) {
            if (!isset($expectedVersions[$version])) {
                $failures[] = "Source de migration disponible {$version}";
            }
        }

        return $failures;
    }
}

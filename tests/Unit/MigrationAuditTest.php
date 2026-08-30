<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use SCTech\Core\MigrationAudit;

final class MigrationAuditTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('PDO SQLite is required for this unit test.');
        }
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sctech-migrations-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory) && is_dir($this->directory)) {
            foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
    }

    public function testItRequiresEveryMigrationWithItsOriginalChecksum(): void
    {
        $file = $this->directory . DIRECTORY_SEPARATOR . '001_schema.sql';
        file_put_contents($file, 'CREATE TABLE example (id INTEGER);');
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE migrations (version TEXT PRIMARY KEY, checksum TEXT NOT NULL)');
        $audit = new MigrationAudit();

        self::assertSame(['Migration appliquée 001_schema'], $audit->failures($pdo, $this->directory));

        $insert = $pdo->prepare('INSERT INTO migrations (version, checksum) VALUES (?, ?)');
        $insert->execute(['001_schema', hash_file('sha256', $file)]);
        self::assertSame([], $audit->failures($pdo, $this->directory));

        $insert->execute(['999_unknown', str_repeat('a', 64)]);
        self::assertSame(
            ['Source de migration disponible 999_unknown'],
            $audit->failures($pdo, $this->directory)
        );
        $pdo->exec("DELETE FROM migrations WHERE version = '999_unknown'");

        file_put_contents($file, 'CREATE TABLE changed (id INTEGER);');
        self::assertSame(
            ['Checksum de migration valide 001_schema'],
            $audit->failures($pdo, $this->directory)
        );
    }
}

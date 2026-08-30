<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use SCTech\Core\LeadRetentionPurger;

final class LeadRetentionPurgerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('PDO SQLite is required for this unit test.');
        }
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE contact_messages (id INTEGER PRIMARY KEY, created_at TEXT NOT NULL)');
        $this->pdo->exec('CREATE TABLE quote_requests (id INTEGER PRIMARY KEY, created_at TEXT NOT NULL)');
        $this->pdo->exec(
            'CREATE TABLE consent_events (id INTEGER PRIMARY KEY, subject_type TEXT, subject_id INTEGER)'
        );
        $this->pdo->exec(
            'CREATE TABLE workflow_metadata (id INTEGER PRIMARY KEY, subject_type TEXT, subject_id INTEGER)'
        );
        $this->pdo->exec("INSERT INTO contact_messages VALUES (1, '2024-01-01'), (2, '2026-07-20')");
        $this->pdo->exec("INSERT INTO quote_requests VALUES (3, '2024-01-01'), (4, '2026-07-20')");
        $this->pdo->exec("INSERT INTO consent_events VALUES (1, 'contact_message', 1), (2, 'quote_request', 3), (3, 'contact_message', 2)");
        $this->pdo->exec("INSERT INTO workflow_metadata VALUES (1, 'contact_message', 1), (2, 'quote_request', 3), (3, 'quote_request', 4)");
    }

    public function testZeroRetentionIsAnExplicitNoOp(): void
    {
        $counts = (new LeadRetentionPurger())->purge($this->pdo, 0);

        self::assertFalse($counts['enabled']);
        self::assertSame(2, $this->tableCount('contact_messages'));
        self::assertSame(2, $this->tableCount('quote_requests'));
    }

    public function testItTransactionallyPurgesOldLeadsAndTheirMetadata(): void
    {
        $now = new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC'));
        $counts = (new LeadRetentionPurger())->purge($this->pdo, 365, $now);

        self::assertSame([
            'enabled' => true,
            'metadata' => 2,
            'consents' => 2,
            'contacts' => 1,
            'quotes' => 1,
        ], $counts);
        self::assertSame(1, $this->tableCount('contact_messages'));
        self::assertSame(1, $this->tableCount('quote_requests'));
        self::assertSame(1, $this->tableCount('consent_events'));
        self::assertSame(1, $this->tableCount('workflow_metadata'));
    }

    public function testFailureRollsBackTheWholeLeadPurge(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TRIGGER prevent_old_quote_delete
            BEFORE DELETE ON quote_requests
            WHEN OLD.id = 3
            BEGIN
                SELECT RAISE(ABORT, 'blocked for rollback test');
            END
            SQL);
        $now = new DateTimeImmutable('2026-08-03 12:00:00', new DateTimeZone('UTC'));

        try {
            (new LeadRetentionPurger())->purge($this->pdo, 365, $now);
            self::fail('The simulated database failure should be propagated.');
        } catch (\PDOException) {
            self::assertSame(2, $this->tableCount('contact_messages'));
            self::assertSame(2, $this->tableCount('quote_requests'));
            self::assertSame(3, $this->tableCount('consent_events'));
            self::assertSame(3, $this->tableCount('workflow_metadata'));
        }
    }

    private function tableCount(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
}

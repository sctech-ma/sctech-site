<?php

declare(strict_types=1);

namespace SCTech\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class DatabaseContractTest extends TestCase
{
    public function testMigrationContainsEveryRequiredTableAndSecurityColumn(): void
    {
        $root = dirname(__DIR__, 2);
        $sql = (string) file_get_contents($root . '/database/migrations/001_initial_schema.sql');
        $tables = [
            'migrations', 'users', 'pages', 'services', 'sectors', 'case_studies', 'articles', 'media',
            'contact_messages', 'quote_requests', 'site_settings', 'login_attempts', 'rate_limits',
            'admin_audit_logs', 'consent_events', 'workflow_metadata',
        ];
        foreach ($tables as $table) {
            self::assertStringContainsString('CREATE TABLE IF NOT EXISTS ' . $table, $sql);
        }
        $columns = [
            'idempotency_key_hash', 'notification_status', 'services_researched_json',
            'preferred_contact_method', 'version INT UNSIGNED', 'bucket_key CHAR(64)',
        ];
        foreach ($columns as $column) {
            self::assertStringContainsString($column, $sql);
        }
        $verificationMigration = (string) file_get_contents(
            $root . '/database/migrations/002_case_study_verification.sql',
        );
        foreach (['verified_at', 'verified_by', 'verification_content_hash'] as $column) {
            self::assertStringContainsString($column, $verificationMigration);
        }
        self::assertStringContainsString("SET status = 'draft'", $verificationMigration);

        $financialMigration = (string) file_get_contents(
            $root . '/database/migrations/003_financial_positioning.sql',
        );
        foreach (
            [
                'project_context TEXT NULL',
                'project_objective TEXT NULL',
                'compliance_requirements TEXT NULL',
                'integration_requirements TEXT NULL',
                'category_key VARCHAR(80) NULL',
                'idx_articles_category',
            ] as $definition
        ) {
            self::assertStringContainsString($definition, $financialMigration);
        }
        foreach (
            [
                'MODIFY COLUMN phone VARCHAR(40) NULL',
                'MODIFY COLUMN preferred_contact_method VARCHAR(40) NULL',
                'MODIFY COLUMN project_summary TEXT NULL',
            ] as $nullableDefinition
        ) {
            self::assertStringContainsString($nullableDefinition, $financialMigration);
        }
        self::assertStringContainsString('ext-pdo', file_get_contents($root . '/composer.json') ?: '');
    }

    public function testConsolidatedSchemaIsStandaloneAndIncludesForwardMigrations(): void
    {
        $root = dirname(__DIR__, 2);
        $migrationPath = $root . '/database/migrations/001_initial_schema.sql';
        $migration = str_replace("\r\n", "\n", (string) file_get_contents($migrationPath));
        $schema = str_replace("\r\n", "\n", (string) file_get_contents($root . '/database/schema.sql'));

        self::assertStringNotContainsString('SOURCE ', $schema);
        self::assertStringStartsWith(trim($migration), trim($schema));
        self::assertStringContainsString('ADD COLUMN verified_at DATETIME(6)', $schema);
        self::assertStringContainsString('fk_case_studies_verified_by', $schema);
        self::assertStringContainsString('ADD COLUMN project_context TEXT NULL', $schema);
        self::assertStringContainsString('ADD COLUMN category_key VARCHAR(80) NULL', $schema);
        self::assertStringContainsString('idx_articles_category', $schema);
    }

    public function testSeedPublishesInsightsButNeverCaseStudiesOrCredentials(): void
    {
        $seed = (string) file_get_contents(dirname(__DIR__, 2) . '/database/seeds/001_safe_content.sql');

        self::assertSame(3, substr_count($seed, "'insight."));
        self::assertStringContainsString("'{\"version\":1,\"blocks\":[]}', 'draft'", $seed);
        self::assertStringNotContainsString('INSERT INTO users', $seed);
        self::assertStringNotContainsString('password_hash', $seed);
        self::assertStringNotContainsString('99.7', $seed);
        self::assertStringNotContainsString('100% conformité', $seed);
    }
}

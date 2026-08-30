<?php

declare(strict_types=1);

namespace SCTech\Core;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

final class LeadRetentionPurger
{
    /**
     * @return array{enabled: bool, metadata: int, consents: int, contacts: int, quotes: int}
     */
    public function purge(PDO $pdo, int $retentionDays, ?DateTimeImmutable $now = null): array
    {
        $counts = ['enabled' => false, 'metadata' => 0, 'consents' => 0, 'contacts' => 0, 'quotes' => 0];
        if ($retentionDays <= 0) {
            return $counts;
        }

        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $cutoff = $now->setTimezone(new DateTimeZone('UTC'))
            ->sub(new DateInterval('P' . $retentionDays . 'D'))
            ->format('Y-m-d H:i:s.u');

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $counts['metadata'] += $this->deleteChildren($pdo, 'workflow_metadata', 'contact_message', 'contact_messages', $cutoff);
            $counts['metadata'] += $this->deleteChildren($pdo, 'workflow_metadata', 'quote_request', 'quote_requests', $cutoff);
            $counts['consents'] += $this->deleteChildren($pdo, 'consent_events', 'contact_message', 'contact_messages', $cutoff);
            $counts['consents'] += $this->deleteChildren($pdo, 'consent_events', 'quote_request', 'quote_requests', $cutoff);
            $counts['contacts'] = $this->deleteLeads($pdo, 'contact_messages', $cutoff);
            $counts['quotes'] = $this->deleteLeads($pdo, 'quote_requests', $cutoff);
            $counts['enabled'] = true;

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction) {
                try {
                    $pdo->rollBack();
                } catch (Throwable) {
                    // Preserve the purge failure if the driver already closed the transaction.
                }
            }
            throw $exception;
        }

        return $counts;
    }

    private function deleteChildren(
        PDO $pdo,
        string $childTable,
        string $subjectType,
        string $leadTable,
        string $cutoff,
    ): int {
        $statement = $pdo->prepare(<<<SQL
            DELETE FROM {$childTable}
            WHERE subject_type = :subject_type
              AND subject_id IN (SELECT id FROM {$leadTable} WHERE created_at < :cutoff)
            SQL);
        $statement->execute(['subject_type' => $subjectType, 'cutoff' => $cutoff]);

        return $statement->rowCount();
    }

    private function deleteLeads(PDO $pdo, string $table, string $cutoff): int
    {
        $statement = $pdo->prepare("DELETE FROM {$table} WHERE created_at < :cutoff");
        $statement->execute(['cutoff' => $cutoff]);

        return $statement->rowCount();
    }
}

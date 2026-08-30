<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use PDO;
use SCTech\DTO\ContactSubmission;
use SCTech\DTO\PersistedLead;
use SCTech\DTO\SubmissionContext;
use Throwable;

final class ContactMessageRepository implements ContactMessageStore
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByIdempotencyHash(string $hash): ?PersistedLead
    {
        $statement = $this->pdo->prepare(
            'SELECT id, public_id, notification_status FROM contact_messages WHERE idempotency_key_hash = :hash LIMIT 1'
        );
        $statement->execute(['hash' => $hash]);
        $row = $statement->fetch();

        return is_array($row)
            ? new PersistedLead((int) $row['id'], (string) $row['public_id'], (string) $row['notification_status'], true)
            : null;
    }

    public function create(
        ContactSubmission $submission,
        SubmissionContext $context,
        string $idempotencyHash,
        string $ipHash,
        string $userAgentHash,
    ): PersistedLead {
        $publicId = self::uuidV4();
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(<<<'SQL'
                INSERT INTO contact_messages
                    (public_id, locale, full_name, email, organisation, phone, subject, message,
                     consent_privacy, idempotency_key_hash, ip_hash, user_agent_hash)
                VALUES
                    (:public_id, :locale, :full_name, :email, :organisation, :phone, :subject, :message,
                     :consent_privacy, :idempotency_hash, :ip_hash, :user_agent_hash)
                SQL);
            $statement->execute([
                'public_id' => $publicId,
                'locale' => $submission->locale,
                'full_name' => $submission->fullName,
                'email' => $submission->email,
                'organisation' => $submission->organisation !== '' ? $submission->organisation : null,
                'phone' => $submission->phone !== '' ? $submission->phone : null,
                'subject' => $submission->subject,
                'message' => $submission->message,
                'consent_privacy' => $submission->consentPrivacy ? 1 : 0,
                'idempotency_hash' => $idempotencyHash,
                'ip_hash' => $ipHash,
                'user_agent_hash' => $userAgentHash !== '' ? $userAgentHash : null,
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $consent = $this->pdo->prepare(<<<'SQL'
                INSERT INTO consent_events
                    (subject_type, subject_id, consent_key, consented, policy_version, ip_hash)
                VALUES ('contact_message', :subject_id, 'privacy', :consented, :policy_version, :ip_hash)
                SQL);
            $consent->execute([
                'subject_id' => $id,
                'consented' => $submission->consentPrivacy ? 1 : 0,
                'policy_version' => $context->policyVersion,
                'ip_hash' => $ipHash,
            ]);

            $this->pdo->commit();

            return new PersistedLead($id, $publicId, 'pending');
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function markNotificationSent(int $id): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE contact_messages
            SET notification_status = 'sent', notification_attempts = notification_attempts + 1,
                notification_error_code = NULL, notified_at = UTC_TIMESTAMP(6)
            WHERE id = :id
            SQL);
        $statement->execute(['id' => $id]);
    }

    public function markNotificationFailed(int $id, string $safeErrorCode): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE contact_messages
            SET notification_status = 'failed', notification_attempts = notification_attempts + 1,
                notification_error_code = :error_code
            WHERE id = :id
            SQL);
        $statement->execute(['id' => $id, 'error_code' => mb_substr($safeErrorCode, 0, 80)]);
    }

    /** @return list<array<string, mixed>> */
    public function paginate(int $page = 1, int $perPage = 25, string $status = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT * FROM contact_messages';
        $params = [];
        if (in_array($status, ['new', 'in_progress', 'closed', 'spam'], true)) {
            $sql .= ' WHERE workflow_status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
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
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM contact_messages WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function updateWorkflowStatus(int $id, string $status, int $expectedVersion): bool
    {
        if (!in_array($status, ['new', 'in_progress', 'closed', 'spam'], true)) {
            return false;
        }
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE contact_messages
            SET workflow_status = :status, version = version + 1
            WHERE id = :id AND version = :version
            SQL);
        $statement->execute(['status' => $status, 'id' => $id, 'version' => $expectedVersion]);

        return $statement->rowCount() === 1;
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}

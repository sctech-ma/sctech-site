<?php

declare(strict_types=1);

namespace SCTech\Repositories;

use JsonException;
use PDO;

final class AdminAuditRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $context */
    public function record(
        ?int $userId,
        string $action,
        ?string $entityType,
        ?int $entityId,
        ?string $requestId,
        ?string $ipHash,
        array $context = [],
    ): void {
        try {
            $contextJson = json_encode($this->redact($context), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $contextJson = '{}';
        }
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO admin_audit_logs
                (user_id, action, entity_type, entity_id, request_id, ip_hash, context_json)
            VALUES (:user_id, :action, :entity_type, :entity_id, :request_id, :ip_hash, :context_json)
            SQL);
        $statement->execute([
            'user_id' => $userId,
            'action' => mb_substr($action, 0, 120),
            'entity_type' => $entityType !== null ? mb_substr($entityType, 0, 80) : null,
            'entity_id' => $entityId,
            'request_id' => $requestId !== null ? mb_substr($requestId, 0, 80) : null,
            'ip_hash' => $ipHash,
            'context_json' => $contextJson,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (preg_match('/password|secret|token|csrf|cookie|authorization/i', (string) $key)) {
                $context[$key] = '[redacted]';
            } else {
                $context[$key] = $this->redactValue($value);
            }
        }

        return $context;
    }

    private function redactValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $redacted = [];
        foreach ($value as $key => $item) {
            $redacted[$key] = preg_match('/password|secret|token|csrf|cookie|authorization/i', (string) $key)
                ? '[redacted]'
                : $this->redactValue($item);
        }

        return $redacted;
    }
}

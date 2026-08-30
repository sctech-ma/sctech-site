<?php

declare(strict_types=1);

namespace SCTech\Services;

use JsonException;
use SCTech\Repositories\AdminAuditRepository;
use SCTech\Repositories\SettingsRepository;
use SCTech\Validation\ValidationResult;

final class SettingsService
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly AdminAuditRepository $audit,
    ) {
    }

    public function save(
        string $locale,
        string $key,
        string $valueJson,
        string $status,
        bool $sensitive,
        ?int $version,
        int $actorId,
        string $requestId,
        string $ipHash,
    ): ValidationResult {
        $errors = [];
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,119}$/', $key)) {
            $errors['content_key'][] = 'Utilisez une clé stable valide.';
        }
        try {
            json_decode($valueJson, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $errors['value_json'][] = 'La valeur n’est pas un JSON valide.';
        }
        if ($errors !== []) {
            return ValidationResult::invalid($errors);
        }
        $id = $this->settings->save($locale, $key, $valueJson, $status, $sensitive, $version);
        $this->audit->record($actorId, 'settings.updated', 'site_setting', $id, $requestId, $ipHash, ['content_key' => $key, 'status' => $status]);

        return ValidationResult::valid();
    }
}

<?php

declare(strict_types=1);

namespace SCTech\Validation;

use JsonException;
use SCTech\DTO\ContentInput;

final class CaseStudyVerificationPolicy
{
    private const MINIMUM_NOTES_LENGTH = 20;
    private const MAXIMUM_NOTES_LENGTH = 2000;

    /** @param array<string, mixed>|null $existing */
    public function validate(ContentInput $input, string $actorRole, ?array $existing): ValidationResult
    {
        $errors = [];
        $isAdministrator = $actorRole === 'admin';

        if ($input->verifyForPublication && !$isAdministrator) {
            $errors['verify_for_publication'][] = 'Seul un administrateur peut valider une réalisation.';
        }

        if ($input->verifyForPublication && $isAdministrator) {
            $notesLength = mb_strlen($input->verificationNotes, 'UTF-8');
            if ($notesLength < self::MINIMUM_NOTES_LENGTH || $notesLength > self::MAXIMUM_NOTES_LENGTH) {
                $errors['verification_notes'][] =
                    'Documentez la source et le périmètre de la validation en 20 à 2 000 caractères.';
            }
        }

        if ($input->status === 'published') {
            if (!$this->hasStructuredNarrative($input->blocksJson)) {
                $errors['blocks_json'][] =
                    'Ajoutez au moins un bloc de contenu validé avant de publier la réalisation.';
            }
            if (!$isAdministrator) {
                $errors['status'][] = 'Seul un administrateur peut publier une réalisation.';
            } elseif (!$input->verifyForPublication && !$this->isCurrent($input, $existing)) {
                $errors['status'][] =
                    'La publication exige une validation administrateur documentée pour cette version du contenu.';
            }
        }

        return $errors === [] ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }

    /** @param array<string, mixed>|null $existing */
    public function isCurrent(ContentInput $input, ?array $existing): bool
    {
        if (
            $existing === null
            || !is_string($existing['verification_content_hash'] ?? null)
            || ($existing['verified_at'] ?? null) === null
            || ($existing['verified_by'] ?? null) === null
            || mb_strlen(trim((string) ($existing['verification_notes'] ?? '')), 'UTF-8')
                < self::MINIMUM_NOTES_LENGTH
        ) {
            return false;
        }

        return hash_equals((string) $existing['verification_content_hash'], $this->contentHash($input));
    }

    public function contentHash(ContentInput $input): string
    {
        try {
            $content = json_encode(
                [$input->locale, $input->title, $input->summary, $input->blocksJson],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Le contenu ne peut pas être signé.', 0, $exception);
        }

        return hash('sha256', $content);
    }

    private function hasStructuredNarrative(string $json): bool
    {
        try {
            $document = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return is_array($document)
            && is_array($document['blocks'] ?? null)
            && $document['blocks'] !== [];
    }
}

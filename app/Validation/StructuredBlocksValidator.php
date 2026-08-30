<?php

declare(strict_types=1);

namespace SCTech\Validation;

use JsonException;

final class StructuredBlocksValidator
{
    private const TYPES = ['paragraph', 'heading', 'list', 'quote', 'callout'];

    public function validateJson(string $json): ValidationResult
    {
        try {
            $document = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ValidationResult::invalid(['blocks_json' => ['Le contenu structuré n’est pas un JSON valide.']]);
        }

        if (!is_array($document) || ($document['version'] ?? null) !== 1 || !is_array($document['blocks'] ?? null)) {
            return ValidationResult::invalid(['blocks_json' => ['Le contenu doit utiliser le format version 1 avec une liste de blocs.']]);
        }
        if (count($document['blocks']) > 100) {
            return ValidationResult::invalid(['blocks_json' => ['Le contenu ne peut pas dépasser 100 blocs.']]);
        }

        foreach ($document['blocks'] as $index => $block) {
            if (!is_array($block) || !in_array($block['type'] ?? null, self::TYPES, true)) {
                return ValidationResult::invalid(['blocks_json' => [sprintf('Le bloc %d utilise un type non autorisé.', $index + 1)]]);
            }
            if (!$this->validBlock($block)) {
                return ValidationResult::invalid(['blocks_json' => [sprintf('Le bloc %d est incomplet ou trop long.', $index + 1)]]);
            }
        }

        return ValidationResult::valid();
    }

    /** @param array<string, mixed> $block */
    private function validBlock(array $block): bool
    {
        $type = $block['type'];
        if ($type === 'paragraph') {
            return $this->text($block['text'] ?? null, 1, 5000);
        }
        if ($type === 'heading') {
            return in_array($block['level'] ?? null, [2, 3], true) && $this->text($block['text'] ?? null, 1, 190);
        }
        if ($type === 'quote') {
            return $this->text($block['text'] ?? null, 1, 1000)
                && (!isset($block['source']) || $this->text($block['source'], 0, 190));
        }
        if ($type === 'callout') {
            return $this->text($block['title'] ?? null, 1, 190) && $this->text($block['text'] ?? null, 1, 2000);
        }
        if ($type === 'list') {
            if (isset($block['title']) && !$this->text($block['title'], 0, 190)) {
                return false;
            }
            if (!is_array($block['items'] ?? null) || count($block['items']) < 1 || count($block['items']) > 30) {
                return false;
            }
            foreach ($block['items'] as $item) {
                if (!$this->text($item, 1, 500)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function text(mixed $value, int $min, int $max): bool
    {
        return is_string($value) && mb_strlen(trim($value), 'UTF-8') >= $min && mb_strlen($value, 'UTF-8') <= $max;
    }
}

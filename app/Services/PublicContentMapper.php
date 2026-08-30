<?php

declare(strict_types=1);

namespace SCTech\Services;

use JsonException;
use SCTech\Validation\StructuredBlocksValidator;

final class PublicContentMapper
{
    public function __construct(private readonly StructuredBlocksValidator $blocksValidator)
    {
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function page(array $row): array
    {
        return [
            'title' => $this->text($row, 'title'),
            'eyebrow' => $this->text($row, 'eyebrow'),
            'summary' => $this->text($row, 'summary'),
            'seo_title' => $this->text($row, 'seo_title'),
            'seo_description' => $this->text($row, 'seo_description'),
            'blocks' => $this->blocks($this->text($row, 'blocks_json')),
        ];
    }

    /** @param array<string, mixed> $fallback
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function expertise(array $fallback, array $row, int $position): array
    {
        $slug = $this->text($row, 'slug');
        $service = $fallback + [
            'number' => str_pad((string) $position, 2, '0', STR_PAD_LEFT),
            'href' => '/expertises/' . rawurlencode($slug),
            'eyebrow' => 'Expertise SCTECH',
            'name' => '',
            'short' => '',
            'lead' => '',
            'problems' => [],
            'capabilities' => [],
            'use_cases' => [],
            'security' => 'Les exigences de sécurité sont qualifiées selon les données, les accès, les flux et la criticité du contexte.',
            'related' => [],
        ];

        $title = $this->text($row, 'title');
        $summary = $this->text($row, 'summary');
        $eyebrow = $this->text($row, 'eyebrow');
        $problem = $this->text($row, 'problem_text');
        $positioning = $this->text($row, 'positioning_text');
        if ($title !== '') {
            $service['name'] = $title;
        }
        if ($summary !== '') {
            $service['short'] = $summary;
            $service['lead'] = $summary;
        }
        if ($eyebrow !== '') {
            $service['eyebrow'] = $eyebrow;
        }
        if ($problem !== '') {
            $service['problems'] = [$problem];
        }
        if ($positioning !== '') {
            $service['positioning'] = $positioning;
        }
        $capabilities = $this->listItems($this->blocks($this->text($row, 'blocks_json')), 'capacit');
        if ($capabilities !== []) {
            $service['capabilities'] = $capabilities;
        }
        $service['slug'] = $slug;
        $service['href'] = '/expertises/' . rawurlencode($slug);
        $service['seo_title'] = $this->text($row, 'seo_title');
        $service['seo_description'] = $this->text($row, 'seo_description');

        return $service;
    }

    /** @param array<string, mixed> $fallback
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function sector(array $fallback, array $row): array
    {
        $sector = $fallback + [
            'name' => '',
            'copy' => '',
            'signal' => 'Contexte métier',
            'capabilities' => [],
        ];
        $slug = $this->text($row, 'slug');
        $title = $this->text($row, 'title');
        $summary = $this->text($row, 'summary');
        $eyebrow = $this->text($row, 'eyebrow');
        if ($title !== '') {
            $sector['name'] = $title;
        }
        if ($summary !== '') {
            $sector['copy'] = $summary;
        }
        if ($eyebrow !== '') {
            $sector['signal'] = $eyebrow;
        }
        $capabilities = $this->listItems($this->blocks($this->text($row, 'blocks_json')), 'capacit');
        if ($capabilities !== []) {
            $sector['capabilities'] = $capabilities;
        }
        $sector['slug'] = $slug;

        return $sector;
    }

    /** @return list<array<string, mixed>> */
    public function blocks(string $json): array
    {
        if ($json === '' || !$this->blocksValidator->validateJson($json)->isValid()) {
            return [];
        }

        try {
            $document = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($document) || !is_array($document['blocks'] ?? null)) {
            return [];
        }

        return array_values(array_filter($document['blocks'], 'is_array'));
    }

    /** @param list<array<string, mixed>> $blocks
     * @return list<string>
     */
    private function listItems(array $blocks, string $titleNeedle): array
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'list' || !is_array($block['items'] ?? null)) {
                continue;
            }
            $title = mb_strtolower((string) ($block['title'] ?? ''), 'UTF-8');
            if ($title !== '' && !str_contains($title, $titleNeedle)) {
                continue;
            }

            return array_values(array_filter(array_map(
                static fn (mixed $item): string => is_string($item) ? trim($item) : '',
                $block['items']
            ), static fn (string $item): bool => $item !== ''));
        }

        return [];
    }

    /** @param array<string, mixed> $row */
    private function text(array $row, string $key): string
    {
        return is_string($row[$key] ?? null) ? trim($row[$key]) : '';
    }
}

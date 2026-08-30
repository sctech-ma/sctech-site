<?php

declare(strict_types=1);

namespace SCTech\Support;

final class ProjectInquiryCatalog
{
    /** @var array<string, string> */
    private const PROJECT_TYPES = [
        'nouveau-produit' => 'Nouveau produit financier',
        'modernisation' => 'Modernisation d’un système existant',
        'extension-fonctionnelle' => 'Extension d’un produit ou workflow',
        'integration' => 'Intégration de systèmes financiers',
        'cadrage' => 'Cadrage ou étude d’architecture',
        'autre' => 'Autre besoin',
    ];

    /** @var array<string, string> */
    private const SOLUTION_DOMAINS = [
        'plateformes-investissement' => 'Plateformes d’investissement',
        'portails-investisseurs' => 'Portails investisseurs',
        'workflows-conformite' => 'Workflows de conformité',
        'data-reporting' => 'Data & reporting',
        'integrations-financieres' => 'Intégrations financières',
    ];

    /** @var array<string, string> */
    private const TIMELINES = [
        'moins-1-mois' => 'Moins d’un mois',
        '1-3-mois' => '1 à 3 mois',
        '3-6-mois' => '3 à 6 mois',
        'plus-6-mois' => 'Plus de 6 mois',
        'a-definir' => 'À définir',
    ];

    /** @var array<string, string> */
    private const BUDGETS = [
        'a-definir' => 'À définir',
        'moins-100k' => 'Moins de 100 000 MAD',
        '100k-300k' => '100 000 à 300 000 MAD',
        '300k-1m' => '300 000 à 1 million MAD',
        'plus-1m' => 'Plus de 1 million MAD',
    ];

    /** @var array<string, string> */
    private const CONTACT_METHODS = [
        'email' => 'E-mail',
        'telephone' => 'Téléphone',
        'visioconference' => 'Visioconférence',
    ];

    /** @return array<string, string> */
    public static function projectTypes(): array
    {
        return self::PROJECT_TYPES;
    }

    /** @return array<string, string> */
    public static function solutionDomains(): array
    {
        return self::SOLUTION_DOMAINS;
    }

    /** @return array<string, string> */
    public static function timelines(): array
    {
        return self::TIMELINES;
    }

    /** @return array<string, string> */
    public static function budgets(): array
    {
        return self::BUDGETS;
    }

    /** @return array<string, string> */
    public static function contactMethods(): array
    {
        return self::CONTACT_METHODS;
    }

    /** @param array<string, string> $options */
    public static function contains(array $options, string $key): bool
    {
        return $key !== '' && array_key_exists($key, $options);
    }

    /** @param array<string, string> $options */
    public static function label(array $options, string $key): string
    {
        return $options[$key] ?? $key;
    }
}

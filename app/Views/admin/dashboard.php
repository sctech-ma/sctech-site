<?php

declare(strict_types=1);

$counts = is_array($counts ?? null) ? $counts : [];
?>
<header class="admin-page-header">
    <div>
        <p class="eyebrow">Pilotage éditorial</p>
        <h1>Tableau de bord</h1>
        <p>Les chiffres ci-dessous comptent uniquement les contenus actuellement publiés.</p>
    </div>
    <a class="button button--secondary" href="/" target="_blank" rel="noopener">Voir le site <span class="sr-only">(nouvel onglet)</span></a>
</header>

<dl class="admin-metrics">
    <?php foreach ([
        'pages' => 'Pages',
        'expertises' => 'Solutions',
        'secteurs' => 'Segments cibles',
        'realisations' => 'Réalisations vérifiées',
        'articles' => 'Articles',
    ] as $key => $label): ?>
        <div>
            <dt><?= e($label) ?></dt>
            <dd><?= e((string) ($counts[$key] ?? 0)) ?></dd>
        </div>
    <?php endforeach; ?>
</dl>

<?php if (($counts['realisations'] ?? 0) === 0): ?>
    <aside class="notice" aria-labelledby="gate-realisations">
        <h2 id="gate-realisations">Aucune réalisation publique</h2>
        <p>Les brouillons restent invisibles jusqu’à la validation écrite du client et de chaque affirmation.</p>
        <a href="/admin/realisations">Examiner les brouillons</a>
    </aside>
<?php endif; ?>

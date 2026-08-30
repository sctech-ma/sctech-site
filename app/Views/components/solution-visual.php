<?php

declare(strict_types=1);

$visualSlug = (string) ($visualSlug ?? ($solution['slug'] ?? 'plateformes-investissement'));
$visualTitle = (string) ($visualTitle ?? ($solution['title'] ?? $solution['name'] ?? 'Solution financière'));
$visualMode = match ($visualSlug) {
    'portails-investisseurs' => 'journey',
    'workflows-conformite' => 'rules',
    'data-reporting' => 'reporting',
    'integrations-financieres' => 'integrations',
    default => 'portfolio',
};
?>
<figure class="solution-visual solution-visual--<?= e($visualMode) ?>" aria-label="Interface conceptuelle : <?= e($visualTitle) ?>">
    <figcaption><span>Interface conceptuelle</span><strong><?= e($visualTitle) ?></strong></figcaption>
    <?php if ($visualMode === 'portfolio'): ?>
        <div class="visual-portfolio">
            <div class="visual-portfolio__head"><span>Portefeuille A</span><b>Vue consolidée</b></div>
            <div class="visual-portfolio__chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
            <dl><div><dt>Actif 01</dt><dd>Qualifié</dd></div><div><dt>Actif 02</dt><dd>À contrôler</dd></div><div><dt>Actif 03</dt><dd>En revue</dd></div></dl>
        </div>
    <?php elseif ($visualMode === 'journey'): ?>
        <ol class="visual-journey">
            <li class="is-done"><span>01</span><div><b>Onboarding</b><small>Dossier structuré</small></div></li>
            <li class="is-current"><span>02</span><div><b>Vérifications</b><small>Validation requise</small></div></li>
            <li><span>03</span><div><b>Souscription</b><small>En attente</small></div></li>
            <li><span>04</span><div><b>Suivi</b><small>Traçabilité continue</small></div></li>
        </ol>
    <?php elseif ($visualMode === 'rules'): ?>
        <div class="visual-rules">
            <header><span>Référentiel actif</span><b>v.04</b></header>
            <div><span>Règle 01</span><p>Critère configuré</p><b>Conforme</b></div>
            <div><span>Règle 02</span><p>Seuil à examiner</p><b>Revue</b></div>
            <div><span>Règle 03</span><p>Justificatif requis</p><b>Attente</b></div>
            <footer>Décision humaine · trace conservée</footer>
        </div>
    <?php elseif ($visualMode === 'reporting'): ?>
        <div class="visual-reporting">
            <header><span>Reporting structuré</span><b>Période active</b></header>
            <div class="visual-reporting__plot" aria-hidden="true"><svg viewBox="0 0 520 180" role="img" aria-label="Courbe de données conceptuelle"><path d="M10 150 C80 146 95 92 155 108 S245 136 294 72 S395 98 510 24"/><path d="M10 164 H510 M10 116 H510 M10 68 H510 M10 20 H510"/></svg></div>
            <ul><li><i></i>Source A</li><li><i></i>Source B</li><li><i></i>Contrôles</li></ul>
        </div>
    <?php else: ?>
        <div class="visual-integrations">
            <span class="visual-integrations__core">Moteur<br><b>SCTECH</b></span>
            <span>Core finance</span><span>Identité</span><span>Données</span><span>Reporting</span>
            <i aria-hidden="true"></i><i aria-hidden="true"></i><i aria-hidden="true"></i><i aria-hidden="true"></i>
        </div>
    <?php endif; ?>
</figure>


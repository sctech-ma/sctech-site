<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$publishedSectors = isset($sectors) && is_array($sectors) ? $sectors : [];
$breadcrumbs = [['label' => 'Secteurs', 'href' => '/secteurs']];
require dirname(__DIR__) . '/components/breadcrumbs.php';
$heroKicker = 'Contextes sectoriels';
$heroTitle = 'La même technologie ne porte pas le même risque partout.';
$heroLead = 'Les flux, les responsabilités, la vitesse de décision et les contraintes de contrôle changent selon le secteur. Notre intervention commence par cette réalité.';
$heroCode = 'CONTEXTES / 04';
$heroAside = 'Comprendre la criticité avant de dimensionner la réponse.';
require dirname(__DIR__) . '/components/page-hero.php';

$sectorDetails = [
    [
        'slug' => 'services-financiers',
        'name' => 'Banque & services financiers',
        'signal' => 'Transactions · Décision · Contrôle',
        'copy' => 'Les parcours doivent conjuguer fluidité, explicabilité, traçabilité et réaction rapide aux comportements à risque.',
        'questions' => ['Comment une décision sensible est-elle justifiée ?', 'Où se concentrent les dépendances transactionnelles ?', 'Quels contrôles sont réellement observables ?'],
        'capabilities' => ['Data & IA', 'Applications financières', 'API & paiements', 'Cybersécurité'],
    ],
    [
        'slug' => 'pme-entreprises',
        'name' => 'PME & entreprises',
        'signal' => 'Productivité · Lisibilité · Évolutivité',
        'copy' => 'La priorité est souvent de simplifier les opérations sans créer une architecture disproportionnée ou difficile à maintenir.',
        'questions' => ['Quel processus crée le plus de friction ?', 'Quelle donnée manque à la décision ?', 'Que faut-il moderniser en premier ?'],
        'capabilities' => ['Transformation digitale', 'Outils métiers', 'BI', 'Cloud'],
    ],
    [
        'slug' => 'ecommerce',
        'name' => 'E-commerce',
        'signal' => 'Conversion · Fraude · Continuité',
        'copy' => 'Les systèmes doivent absorber les variations, orchestrer les partenaires et distinguer le risque sans dégrader les parcours légitimes.',
        'questions' => ['Quels signaux précèdent une perte ?', 'Comment isoler une défaillance partenaire ?', 'Quel arbitrage entre friction et contrôle ?'],
        'capabilities' => ['Détection de fraude', 'Intégrations', 'Observabilité', 'Data'],
    ],
    [
        'slug' => 'energie',
        'name' => 'Énergie',
        'signal' => 'Terrain · Données · Continuité',
        'copy' => 'Les données distribuées et les contraintes opérationnelles exigent une architecture robuste, observable et adaptée aux réalités du terrain.',
        'questions' => ['Où la donnée perd-elle sa qualité ?', 'Quels systèmes doivent rester disponibles ?', 'Comment prioriser les alertes opérationnelles ?'],
        'capabilities' => ['Plateformes data', 'Cloud hybride', 'Résilience', 'Aide à la décision'],
    ],
];
$publishedBySlug = [];
foreach ($publishedSectors as $publishedSector) {
    if (!is_array($publishedSector) || !is_string($publishedSector['slug'] ?? null)) {
        continue;
    }
    $publishedBySlug[$publishedSector['slug']] = $publishedSector;
}
foreach ($sectorDetails as $index => $sector) {
    $slug = (string) ($sector['slug'] ?? '');
    if (isset($publishedBySlug[$slug])) {
        $sectorDetails[$index] = array_replace($sector, $publishedBySlug[$slug]);
        unset($publishedBySlug[$slug]);
    }
}
foreach ($publishedBySlug as $sector) {
    $sectorDetails[] = $sector + [
        'name' => 'Secteur',
        'signal' => 'Contexte métier',
        'copy' => '',
        'questions' => [],
        'capabilities' => [],
    ];
}
?>
<section class="section sector-detail-section" aria-labelledby="sector-detail-title">
    <div class="shell">
        <header class="section-heading" data-reveal>
            <div><p class="section-kicker">Matrices de contexte</p><h2 id="sector-detail-title">Lire les forces qui structurent le besoin.</h2></div>
            <p>Ces repères ouvrent le cadrage. Ils ne remplacent pas l’analyse de votre organisation, de vos systèmes et de vos obligations.</p>
        </header>
        <div class="sector-detail-list">
            <?php foreach ($sectorDetails as $index => $sector): ?>
                <article class="sector-detail" data-reveal>
                    <div class="sector-detail__heading"><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><div><p><?= e($sector['signal']) ?></p><h3><?= e($sector['name']) ?></h3></div></div>
                    <p class="sector-detail__copy"><?= e($sector['copy']) ?></p>
                    <div class="sector-detail__questions">
                        <h4>Questions structurantes</h4>
                        <ul><?php foreach ($sector['questions'] as $question): ?><li><?= e($question) ?></li><?php endforeach; ?></ul>
                    </div>
                    <ul class="tag-list"><?php foreach ($sector['capabilities'] as $capability): ?><li><?= e($capability) ?></li><?php endforeach; ?></ul>
                    <a class="text-link" href="/contact?secteur=<?= e(rawurlencode($sector['name'])) ?>">Parler de ce contexte <span aria-hidden="true">→</span></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--paper context-framework" aria-labelledby="context-framework-title">
    <div class="shell split-heading">
        <div data-reveal><p class="section-kicker">Au-delà du secteur</p><h2 id="context-framework-title">Quatre variables qui changent toute l’intervention.</h2></div>
        <ol class="signal-stack signal-stack--light">
            <li data-reveal><span>01</span><div><h3>Criticité</h3><p>L’impact réel d’une erreur, d’un retard ou d’une indisponibilité.</p></div></li>
            <li data-reveal><span>02</span><div><h3>Temporalité</h3><p>Le rythme auquel le système reçoit, décide et doit réagir.</p></div></li>
            <li data-reveal><span>03</span><div><h3>Responsabilité</h3><p>Les personnes qui autorisent, contrôlent, expliquent et corrigent.</p></div></li>
            <li data-reveal><span>04</span><div><h3>Dépendances</h3><p>Les fournisseurs, systèmes et données dont dépend la continuité.</p></div></li>
        </ol>
    </div>
</section>

<?php
$ctaTitle = 'Votre secteur est un contexte. Votre organisation en est un autre.';
$ctaCopy = 'Un échange nous permettra de qualifier les flux critiques, les contraintes et le niveau de maîtrise attendu.';
$ctaLabel = 'Parler de mon contexte';
require dirname(__DIR__) . '/components/cta-band.php';
?>

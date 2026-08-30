<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$publishedPage = is_array($cmsPage ?? null) ? $cmsPage : [];
$allowedSolutionSlugs = [
    'plateformes-investissement', 'portails-investisseurs', 'workflows-conformite',
    'data-reporting', 'integrations-financieres',
];
$solutionFallbacks = [
    'plateformes-investissement' => [
        'slug' => 'plateformes-investissement', 'number' => '01', 'title' => 'Plateformes d’investissement',
        'eyebrow' => 'Piloter le cycle de vie des actifs',
        'summary' => 'Des plateformes qui relient qualification, allocation, contrôles, validations et suivi dans une même architecture métier.',
        'capabilities' => ['Portefeuilles configurables', 'Workflows de décision', 'Traçabilité des opérations'],
    ],
    'portails-investisseurs' => [
        'slug' => 'portails-investisseurs', 'number' => '02', 'title' => 'Portails investisseurs',
        'eyebrow' => 'Orchestrer une relation exigeante',
        'summary' => 'Des parcours sécurisés pour l’onboarding, les documents, les souscriptions, les validations et le suivi investisseur.',
        'capabilities' => ['Onboarding guidé', 'Espaces documentaires', 'Suivi des validations'],
    ],
    'workflows-conformite' => [
        'slug' => 'workflows-conformite', 'number' => '03', 'title' => 'Workflows de conformité',
        'eyebrow' => 'Transformer un référentiel en opérations',
        'summary' => 'Des règles configurables, contrôles explicables, étapes de revue humaine et traces adaptées à votre gouvernance.',
        'capabilities' => ['Moteur de règles', 'Validation multi-niveaux', 'Journal d’audit'],
    ],
    'data-reporting' => [
        'slug' => 'data-reporting', 'number' => '04', 'title' => 'Data & reporting',
        'eyebrow' => 'Fiabiliser ce qui éclaire la décision',
        'summary' => 'Des chaînes de données structurées pour qualifier les informations, produire les indicateurs et expliquer leur origine.',
        'capabilities' => ['Modèles de données', 'Contrôles de qualité', 'Rapports configurables'],
    ],
    'integrations-financieres' => [
        'slug' => 'integrations-financieres', 'number' => '05', 'title' => 'Intégrations financières',
        'eyebrow' => 'Faire circuler les données avec maîtrise',
        'summary' => 'Des API et orchestrations robustes entre vos systèmes financiers, référentiels, identités et outils de reporting.',
        'capabilities' => ['API sécurisées', 'Orchestration des flux', 'Observabilité technique'],
    ],
];
$candidateSolutions = is_array($solutions ?? null)
    ? $solutions
    : (is_array($lang['financial_solutions'] ?? null) ? $lang['financial_solutions'] : []);
$homeSolutions = [];
foreach ($candidateSolutions as $key => $candidate) {
    if (!is_array($candidate)) {
        continue;
    }
    $slug = (string) ($candidate['slug'] ?? (is_string($key) ? $key : ''));
    if (in_array($slug, $allowedSolutionSlugs, true)) {
        $homeSolutions[$slug] = array_replace($solutionFallbacks[$slug], $candidate);
    }
}
foreach ($solutionFallbacks as $slug => $fallback) {
    $homeSolutions[$slug] ??= $fallback;
}
$articles = is_array($articles ?? null) ? $articles : [];
$publishedCases = array_values(array_filter(
    is_array($caseStudies ?? null) ? $caseStudies : [],
    static fn (array $case): bool => ($case['status'] ?? '') === 'published'
));
$fallbackPaths = [
    'solutions.index' => '/solutions', 'solutions.show' => '/solutions/{slug}',
    'expertise' => '/expertise', 'islamic-finance' => '/finance-islamique',
    'approach' => '/approche', 'project' => '/demander-un-projet',
    'realizations.show' => '/realisations/{slug}', 'insights.index' => '/insights',
];
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$to = static function (string $name, array $parameters = [], array $query = []) use ($routeResolver, $fallbackPaths): string {
    if ($routeResolver !== null) {
        try {
            return (string) $routeResolver($name, $parameters, $query);
        } catch (Throwable) {
        }
    }
    $path = $fallbackPaths[$name] ?? '/';
    foreach ($parameters as $key => $value) {
        $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
    }
    return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};
$method = [
    ['Découvrir', 'Comprendre le modèle, les utilisateurs et les contraintes réelles.'],
    ['Formaliser', 'Transformer les principes et règles métier en décisions explicites.'],
    ['Architecturer', 'Définir les flux, les données, les responsabilités et les frontières.'],
    ['Prototyper', 'Rendre les parcours et les validations testables avant de construire.'],
    ['Développer', 'Livrer par incréments sûrs, lisibles et documentés.'],
    ['Valider', 'Tester les règles, les droits, les cas limites et la traçabilité.'],
    ['Déployer', 'Préparer exploitation, observabilité, reprise et transfert.'],
    ['Faire évoluer', 'Versionner le produit et ses règles sans perdre leur histoire.'],
];
?>
<section class="pp-hero" aria-labelledby="home-title">
    <div class="shell pp-hero__grid">
        <div class="pp-hero__copy" data-reveal>
            <p class="eyebrow">FINTECH SUR MESURE · FINANCE ISLAMIQUE</p>
            <h1 id="home-title"><?= e(trim((string) ($publishedPage['title'] ?? '')) !== '' ? (string) $publishedPage['title'] : 'Votre logique financière. Construite dans le produit.') ?></h1>
            <p class="pp-hero__lead"><?= e(trim((string) ($publishedPage['summary'] ?? '')) !== '' ? (string) $publishedPage['summary'] : 'SCTECH conçoit des plateformes, portails et infrastructures financières sur mesure capables d’intégrer vos règles métier et les exigences de conformité Charia définies par vos instances compétentes.') ?></p>
            <div class="button-group">
                <a class="button button--forest" href="<?= e($to('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a>
                <a class="button button--outline" href="<?= e($to('solutions.index')) ?>">Explorer nos solutions <span aria-hidden="true">→</span></a>
            </div>
            <dl class="pp-hero__coordinates">
                <div><dt>Conception</dt><dd>Sur mesure</dd></div>
                <div><dt>Spécialisation</dt><dd>Finance & Data</dd></div>
                <div><dt>Base</dt><dd>Casablanca · Maroc–Europe</dd></div>
            </dl>
        </div>
        <div class="pp-hero__visual" data-reveal data-reveal-delay="1">
            <?php require dirname(__DIR__) . '/components/financial-operating-layer.php'; ?>
        </div>
    </div>
</section>

<aside class="principles-bar" aria-label="Principes de conception SCTECH">
    <div class="shell"><span>Architecture sur mesure</span><span>Finance & Data</span><span>Sécurité by design</span><span>Workflows de conformité</span><span>Interopérabilité</span></div>
</aside>

<section class="section manifesto" aria-labelledby="manifesto-title">
    <div class="shell manifesto__grid">
        <p class="eyebrow" data-reveal>UN LOGICIEL À LA MESURE DU MODÈLE</p>
        <h2 id="manifesto-title" data-reveal>Vos contraintes financières ne devraient pas s’adapter au logiciel. <em>Le logiciel devrait être construit autour d’elles.</em></h2>
        <p data-reveal>Nous partons de vos opérations, de votre gouvernance et de la façon dont une décision doit être expliquée. L’architecture vient ensuite — pour rendre cette logique durable, contrôlable et évolutive.</p>
    </div>
</section>

<section class="section section--sage operating-section" aria-labelledby="operating-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">DE L’INTENTION AU SYSTÈME</p><h2 id="operating-title">Trois mouvements. Une logique continue.</h2></div><p>Chaque couche prépare la suivante : formaliser ce qui compte, l’inscrire dans l’architecture, puis l’exploiter sans perdre le contexte.</p></header>
        <div class="operating-panels" data-panel-progress>
            <article data-panel-step data-reveal><span>01 / CONCEVOIR</span><div><h3>Rendre la logique explicite.</h3><p>Cartographier les acteurs, événements, décisions, exceptions et preuves nécessaires avant de figer l’interface ou le modèle de données.</p><ul><li>Règles métier</li><li>Parcours et rôles</li><li>Scénarios limites</li></ul></div></article>
            <article data-panel-step data-reveal><span>02 / INTÉGRER</span><div><h3>Relier sans diluer le contrôle.</h3><p>Connecter les sources, identités et systèmes financiers avec des contrats d’échange, des droits et des comportements de repli lisibles.</p><ul><li>API et données</li><li>RBAC et validations</li><li>Traçabilité</li></ul></div></article>
            <article data-panel-step data-reveal><span>03 / OPÉRER</span><div><h3>Faire évoluer sans perdre l’histoire.</h3><p>Observer les traitements, versionner les règles, documenter les décisions et donner aux équipes les moyens d’exploiter le système dans la durée.</p><ul><li>Observabilité</li><li>Versionnement</li><li>Maintenabilité</li></ul></div></article>
        </div>
    </div>
</section>

<section class="section compliance-sequence" aria-labelledby="compliance-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">FINANCE ISLAMIQUE · LOGIQUE APPLICATIVE</p><h2 id="compliance-title">Du référentiel validé à une exécution traçable.</h2></div><p>Le produit ne remplace pas les instances compétentes. Il organise la traduction, l’application et la preuve des règles qu’elles définissent.</p></header>
        <ol class="compliance-flow">
            <?php foreach (['Référentiel défini', 'Formalisation', 'Règles configurées', 'Contrôles', 'Validation humaine', 'Traçabilité & reporting'] as $index => $step): ?>
                <li data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><strong><?= e($step) ?></strong></li>
            <?php endforeach; ?>
        </ol>
        <div data-reveal><?php require dirname(__DIR__) . '/components/sharia-governance.php'; ?></div>
    </div>
</section>

<section class="section solutions-narrative" aria-labelledby="solutions-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">CINQ SYSTÈMES À CONSTRUIRE</p><h2 id="solutions-title">Des solutions distinctes. Une architecture cohérente.</h2></div><a class="text-link" href="<?= e($to('solutions.index')) ?>">Voir toutes les solutions <span aria-hidden="true">→</span></a></header>
        <div class="solution-stories">
            <?php foreach (array_values($homeSolutions) as $index => $solution): ?>
                <article class="solution-story<?= $index % 2 === 1 ? ' solution-story--reverse' : '' ?>" data-reveal>
                    <div class="solution-story__visual"><?php $visualSlug = (string) $solution['slug']; $visualTitle = (string) $solution['title']; require dirname(__DIR__) . '/components/solution-visual.php'; ?></div>
                    <div class="solution-story__copy">
                        <span><?= e((string) $solution['number']) ?> / 05</span><p class="eyebrow"><?= e((string) $solution['eyebrow']) ?></p><h3><?= e((string) $solution['title']) ?></h3><p><?= e((string) $solution['summary']) ?></p>
                        <ul><?php foreach ((array) $solution['capabilities'] as $capability): ?><li><?= e((string) $capability) ?></li><?php endforeach; ?></ul>
                        <a class="text-link" href="<?= e($to('solutions.show', ['slug' => $solution['slug']])) ?>">Explorer cette solution <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="architecture-rail" aria-labelledby="architecture-title">
    <div class="shell architecture-rail__grid">
        <div data-reveal><p class="eyebrow eyebrow--mint">LE SOCLE INVISIBLE</p><h2 id="architecture-title">La précision métier a besoin d’une ingénierie qui tient.</h2><p>Le produit reste compréhensible côté métier et défendable côté technique. Les contrôles ne sont pas des promesses : ils vivent dans les droits, les contrats, les traces et l’exploitation.</p><a class="button button--light" href="<?= e($to('expertise')) ?>">Découvrir notre expertise <span aria-hidden="true">↗</span></a></div>
        <ul data-reveal><li><span>01</span>Architecture modulaire</li><li><span>02</span>API contractuelles</li><li><span>03</span>RBAC & séparation des rôles</li><li><span>04</span>Auditabilité</li><li><span>05</span>Données structurées</li><li><span>06</span>Observabilité</li><li><span>07</span>Maintenabilité</li></ul>
    </div>
</section>

<section class="section possibility-section" aria-labelledby="possibility-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">CAS D’USAGE POSSIBLES</p><h2 id="possibility-title">Des capacités à configurer, jamais des résultats revendiqués.</h2></div><p>Ces exemples illustrent ce qu’un système sur mesure peut organiser. Ils ne constituent ni des références clients ni une promesse de performance.</p></header>
        <div class="possibility-grid">
            <?php foreach ([
                ['Qualification d’actifs', 'Appliquer un référentiel configurable, signaler les écarts et organiser la revue.'],
                ['Onboarding investisseur', 'Coordonner identité, documents, vérifications, accords et relances.'],
                ['Comité de validation', 'Préparer les dossiers, tracer les avis, gérer les réserves et conserver la décision.'],
                ['Suivi de portefeuille', 'Relier événements, données, contrôles périodiques et rapports selon les rôles.'],
                ['Screening financier', 'Produire une qualification explicable avec sources, seuils et règles versionnées.'],
                ['Reporting de conformité', 'Assembler les traces et indicateurs requis sans retraitement opaque.'],
            ] as $index => [$title, $copy]): ?><article data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></article><?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--sage method-preview" aria-labelledby="method-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">LA MÉTHODE SCTECH</p><h2 id="method-title">Huit étapes pour réduire l’ambiguïté.</h2></div><a class="text-link" href="<?= e($to('approach')) ?>">Voir l’approche complète <span aria-hidden="true">→</span></a></header>
        <ol class="method-grid"><?php foreach ($method as $index => [$name, $copy]): ?><li data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e($name) ?></h3><p><?= e($copy) ?></p></li><?php endforeach; ?></ol>
    </div>
</section>

<section class="section proof-section" aria-labelledby="proof-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">RÉALISATIONS</p><h2 id="proof-title">La preuve doit être autorisée avant d’être publiée.</h2></div><p>Aucun client, volume, résultat ou témoignage n’est inventé pour remplir cette page.</p></header>
        <?php if ($publishedCases !== []): ?>
            <div class="case-grid"><?php foreach (array_slice($publishedCases, 0, 2) as $case): ?><article class="case-card" data-reveal><p class="eyebrow"><?= e((string) ($case['sector'] ?? 'Réalisation vérifiée')) ?></p><h3><a href="<?= e($to('realizations.show', ['slug' => (string) $case['slug']])) ?>"><?= e((string) $case['title']) ?></a></h3><p><?= e((string) ($case['summary'] ?? '')) ?></p></article><?php endforeach; ?></div>
        <?php else: ?>
            <div class="reference-panel" data-reveal><div class="reference-panel__seal" aria-hidden="true"><span>SCT</span><i></i><b>RÉFÉRENCES</b></div><div><p class="eyebrow">PARTAGE CONTEXTUEL</p><h3>Demandez les références pertinentes pour votre projet.</h3><p>Lorsque le contexte est qualifié, SCTECH présente uniquement les informations que leur niveau de confidentialité et leur autorisation permettent de partager.</p><a class="button button--outline" href="<?= e($to('project', [], ['objet' => 'references'])) ?>">Préparer un échange <span aria-hidden="true">→</span></a></div></div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--sage insights-preview" aria-labelledby="insights-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">INSIGHTS FINANCIERS</p><h2 id="insights-title">Mettre la logique à plat avant de la coder.</h2></div><a class="text-link" href="<?= e($to('insights.index')) ?>">Tous les insights <span aria-hidden="true">→</span></a></header>
        <?php if ($articles !== []): ?><div class="article-grid"><?php foreach (array_slice($articles, 0, 3) as $article): ?><?php require dirname(__DIR__) . '/components/article-card.php'; ?><?php endforeach; ?></div><?php else: ?><div class="empty-state"><p class="eyebrow">PUBLICATION EN PRÉPARATION</p><h3>Les analyses financières seront publiées ici.</h3><p>En attendant, vous pouvez nous soumettre une question liée à votre architecture ou à vos workflows.</p></div><?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/components/cta-band.php'; ?>

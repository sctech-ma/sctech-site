<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name, array $parameters = [], array $query = []) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name, $parameters, $query);
    }
    $path = $name === 'solutions.show' ? '/solutions/' . rawurlencode((string) ($parameters['slug'] ?? '')) : ($name === 'project' ? '/demander-un-projet' : '/finance-islamique');
    return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};
$breadcrumbs = [['label' => 'Finance islamique', 'href' => $pathFor('islamic-finance')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--finance">
    <div class="shell page-intro__grid"><div data-reveal><p class="eyebrow">FINANCE ISLAMIQUE · LOGIQUE LOGICIELLE</p><h1>Rendre un référentiel applicable, explicable et traçable.</h1></div><div data-reveal><p>SCTECH conçoit les mécanismes logiciels qui traduisent les exigences définies par les instances compétentes du client en données, règles, contrôles, validations et rapports.</p><a class="button button--forest" href="<?= e($pathFor('project', [], ['conformite' => 'finance-islamique'])) ?>">Étudier votre contexte <span aria-hidden="true">↗</span></a></div></div>
</header>

<section class="section finance-boundary" aria-labelledby="finance-boundary-title">
    <div class="shell"><div data-reveal><?php require dirname(__DIR__) . '/components/sharia-governance.php'; ?></div><div class="boundary-grid" aria-labelledby="finance-boundary-title"><section data-reveal><p class="eyebrow">LE LOGICIEL PEUT</p><h2 id="finance-boundary-title">Organiser l’application du référentiel.</h2><ul><li>Représenter des critères et seuils configurables</li><li>Identifier les données et justificatifs requis</li><li>Signaler les cas qui exigent une revue</li><li>Orchestrer une validation humaine</li><li>Conserver la règle, les sources et la décision</li><li>Produire des rapports structurés</li></ul></section><section data-reveal><p class="eyebrow">LE LOGICIEL NE PEUT PAS</p><h2>Se substituer à l’autorité compétente.</h2><ul><li>Définir seul un critère religieux</li><li>Émettre un avis ou une certification</li><li>Garantir la conformité d’un produit</li><li>Remplacer l’interprétation d’un cas nouveau</li><li>Donner un conseil en investissement</li><li>Transformer une règle ambiguë en vérité automatique</li></ul></section></div></div>
</section>

<section class="section section--sage translation-section" aria-labelledby="translation-title">
    <div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">TRADUIRE SANS DÉFORMER</p><h2 id="translation-title">Six niveaux entre le principe et l’opération.</h2></div><p>Chaque niveau possède un responsable, une version et un critère de validation avant de devenir une capacité du produit.</p></header><ol class="translation-layers">
        <li data-reveal><span>01</span><div><h3>Référentiel défini</h3><p>Les sources, autorités, versions et périmètres applicables sont identifiés par le client.</p></div><b>Instance compétente</b></li>
        <li data-reveal><span>02</span><div><h3>Exigence formalisée</h3><p>La règle est décrite avec ses données, exceptions, seuils, cas indéterminés et preuve attendue.</p></div><b>Métier + conformité</b></li>
        <li data-reveal><span>03</span><div><h3>Modèle configurable</h3><p>Les éléments susceptibles d’évoluer sont séparés du comportement structurel de la plateforme.</p></div><b>Produit + ingénierie</b></li>
        <li data-reveal><span>04</span><div><h3>Contrôles exécutés</h3><p>Chaque résultat conserve les entrées, la version de règle et la raison du statut.</p></div><b>Système</b></li>
        <li data-reveal><span>05</span><div><h3>Validation humaine</h3><p>Les cas réservés, ambigus ou exceptionnels suivent le circuit d’autorité prévu.</p></div><b>Responsable désigné</b></li>
        <li data-reveal><span>06</span><div><h3>Trace & reporting</h3><p>La décision, ses justificatifs et son évolution restent disponibles selon les droits.</p></div><b>Audit & gouvernance</b></li>
    </ol></div>
</section>

<section class="rule-anatomy" aria-labelledby="rule-anatomy-title"><div class="shell rule-anatomy__grid"><div data-reveal><p class="eyebrow eyebrow--mint">ANATOMIE D’UNE RÈGLE</p><h2 id="rule-anatomy-title">Une décision explicable a besoin de plus qu’un résultat binaire.</h2><p>L’exemple ci-contre est conceptuel. Il illustre la structure technique d’une règle, sans représenter un critère religieux réel.</p></div><div class="rule-card" data-reveal><header><span>RÈGLE-04</span><b>Version active</b></header><dl><div><dt>Source</dt><dd>Référentiel validé</dd></div><div><dt>Entrées</dt><dd>Données requises</dd></div><div><dt>Condition</dt><dd>Expression configurable</dd></div><div><dt>Résultat</dt><dd>Qualifié · Revue · Non déterminé</dd></div><div><dt>Autorité</dt><dd>Rôle validateur</dd></div><div><dt>Preuve</dt><dd>Sources + version + décision</dd></div></dl><footer>Exemple neutre · aucune règle religieuse réelle</footer></div></div></section>

<section class="section governance-section" aria-labelledby="governance-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">GOUVERNANCE DU CHANGEMENT</p><h2 id="governance-title">Une règle évolue. Son histoire ne doit pas disparaître.</h2></div><p>Le système distingue la proposition, l’approbation, l’activation et l’application afin qu’une modification n’altère jamais silencieusement les décisions passées.</p></header><div class="governance-cycle" data-reveal><span>Proposition</span><i></i><span>Revue compétente</span><i></i><span>Tests</span><i></i><span>Activation datée</span><i></i><span>Suivi</span></div><div class="governance-controls"><article data-reveal><h3>Version immuable</h3><p>Une décision reste liée à la version réellement appliquée au moment du contrôle.</p></article><article data-reveal><h3>Double regard</h3><p>Les changements sensibles peuvent exiger des rôles distincts pour proposer et activer.</p></article><article data-reveal><h3>Cas de test</h3><p>Les scénarios approuvés vérifient les évolutions avant leur mise en service.</p></article></div></div></section>

<section class="section section--sage finance-solutions" aria-labelledby="finance-solutions-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">SOLUTIONS CONCERNÉES</p><h2 id="finance-solutions-title">Intégrer la logique là où le travail se déroule.</h2></div></header><div class="related-grid"><?php foreach (['workflows-conformite', 'plateformes-investissement', 'data-reporting'] as $slug): ?><?php $item = $lang['financial_solutions'][$slug]; ?><a href="<?= e($pathFor('solutions.show', ['slug' => $slug])) ?>"><span><?= e((string) $item['number']) ?></span><h3><?= e((string) $item['title']) ?></h3><p><?= e((string) $item['summary']) ?></p><b>Explorer →</b></a><?php endforeach; ?></div></div></section>

<?php
$ctaKicker = 'UN RÉFÉRENTIEL À OPÉRATIONNALISER';
$ctaTitle = 'Votre gouvernance définit les critères. Construisons le système qui les applique.';
$ctaCopy = 'Le premier échange porte sur les instances, les flux, les données, les points de validation et les systèmes déjà en place.';
$ctaHref = $pathFor('project', [], ['conformite' => 'finance-islamique']);
require dirname(__DIR__) . '/components/cta-band.php';
?>

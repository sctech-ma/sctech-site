<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$solution = is_array($solution ?? null) ? $solution : [];
$slug = (string) ($solution['slug'] ?? ($slug ?? ''));
$fallback = is_array($lang['financial_solutions'][$slug] ?? null) ? $lang['financial_solutions'][$slug] : [];
$solution = array_replace($fallback, $solution);
$title = (string) ($solution['title'] ?? $solution['name'] ?? 'Solution financière');
$relatedSlugs = is_array($solution['related'] ?? null) ? $solution['related'] : [];
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name, array $parameters = [], array $query = []) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name, $parameters, $query);
    }
    $path = match ($name) {
        'solutions.index' => '/solutions',
        'solutions.show' => '/solutions/' . rawurlencode((string) ($parameters['slug'] ?? '')),
        'project' => '/demander-un-projet',
        default => '/',
    };
    return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};
$breadcrumbs = [
    ['label' => 'Solutions', 'href' => $pathFor('solutions.index')],
    ['label' => $title, 'href' => $pathFor('solutions.show', ['slug' => $slug])],
];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="solution-hero">
    <div class="shell solution-hero__grid">
        <div class="solution-hero__copy" data-reveal>
            <p class="eyebrow">SOLUTION <?= e((string) ($solution['number'] ?? '')) ?> · SUR MESURE</p>
            <h1><?= e($title) ?></h1>
            <p><?= e((string) ($solution['summary'] ?? '')) ?></p>
            <div class="button-group"><a class="button button--forest" href="<?= e($pathFor('project', [], ['solution' => $slug])) ?>">Étudier ce projet <span aria-hidden="true">↗</span></a><a class="button button--outline" href="#capacites">Voir les capacités <span aria-hidden="true">↓</span></a></div>
        </div>
        <div class="solution-hero__visual" data-reveal data-reveal-delay="1"><?php $visualSlug = $slug; $visualTitle = $title; require dirname(__DIR__) . '/components/solution-visual.php'; ?></div>
    </div>
</header>

<section class="section solution-context" aria-labelledby="solution-context-title">
    <div class="shell editorial-split">
        <div data-reveal><p class="eyebrow">CONTEXTE MÉTIER</p><h2 id="solution-context-title">La friction apparaît lorsque le logiciel décide déjà comment vous devez travailler.</h2></div>
        <div class="prose-large" data-reveal><p><?= e((string) ($solution['problem_text'] ?? '')) ?></p><p><strong>Positionnement SCTECH.</strong> <?= e((string) ($solution['positioning_text'] ?? '')) ?></p></div>
    </div>
</section>

<section class="section section--sage stakeholder-section" aria-labelledby="stakeholders-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">ACTEURS & RÈGLES</p><h2 id="stakeholders-title">Construire autour de ceux qui proposent, contrôlent et décident.</h2></div><p>La logique configurable sépare ce qui doit pouvoir évoluer de ce qui doit rester une garantie d’architecture.</p></header>
        <div class="dual-list">
            <section data-reveal><span>A / PARTIES PRENANTES</span><ul><?php foreach ((array) ($solution['stakeholders'] ?? []) as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?></ul></section>
            <section data-reveal><span>B / LOGIQUE CONFIGURABLE</span><ul><?php foreach ((array) ($solution['configurable_logic'] ?? []) as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?></ul></section>
        </div>
    </div>
</section>

<section class="section capability-section" id="capacites" aria-labelledby="capabilities-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">CAPACITÉS</p><h2 id="capabilities-title">Les composants utiles au travail, pas un écran de démonstration.</h2></div><p>Le périmètre est sélectionné et séquencé selon la valeur opérationnelle, les dépendances et le niveau de risque.</p></header>
        <div class="capability-grid"><?php foreach ((array) ($solution['capabilities'] ?? []) as $index => $item): ?><article data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e((string) $item) ?></h3><p>Conçu avec les états, permissions, exceptions et traces nécessaires à votre contexte.</p></article><?php endforeach; ?></div>
    </div>
</section>

<section class="workflow-band" aria-labelledby="workflow-title">
    <div class="shell"><div data-reveal><p class="eyebrow eyebrow--mint">WORKFLOW CONCEPTUEL</p><h2 id="workflow-title">Chaque état doit dire ce qui vient de se passer — et ce qui reste à décider.</h2></div><ol><?php foreach ((array) ($solution['workflow'] ?? []) as $index => $item): ?><li data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><strong><?= e((string) $item) ?></strong></li><?php endforeach; ?></ol></div>
</section>

<section class="section integration-section" aria-labelledby="integration-title">
    <div class="shell editorial-split">
        <div data-reveal><p class="eyebrow">DONNÉES & INTÉGRATIONS</p><h2 id="integration-title">Une frontière explicite avec chaque système.</h2><p>Les interfaces sont contractuelles, versionnées et observables. Une panne, un doublon ou une donnée incomplète suit un comportement défini.</p></div>
        <ul class="integration-list" data-reveal><?php foreach ((array) ($solution['integrations'] ?? []) as $item): ?><li><span aria-hidden="true"></span><?= e((string) $item) ?></li><?php endforeach; ?></ul>
    </div>
</section>

<section class="section section--sage security-section" aria-labelledby="security-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">SÉCURITÉ & AUDITABILITÉ</p><h2 id="security-title">Prouver qui pouvait faire quoi, selon quelle règle.</h2></div><p>La sécurité est traitée dans les parcours, le modèle d’autorisation, les données, les composants et l’exploitation.</p></header>
        <div class="security-ledger"><?php foreach ((array) ($solution['security'] ?? []) as $index => $item): ?><article data-reveal><span>CTRL-<?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e((string) $item) ?></h3><b>Conception intégrée</b></article><?php endforeach; ?></div>
        <?php if (!empty($solution['sharia_relevant'])): ?><div data-reveal><?php require dirname(__DIR__) . '/components/sharia-governance.php'; ?></div><?php endif; ?>
    </div>
</section>

<?php if (!empty($cmsBlocks) && is_array($cmsBlocks)): ?>
    <section class="section editorial-extension" aria-labelledby="editorial-extension-title"><div class="shell editorial-split"><div><p class="eyebrow">ÉCLAIRAGE ÉDITORIAL</p><h2 id="editorial-extension-title">Précisions sur cette solution.</h2></div><div class="structured-content"><?php $blocks = $cmsBlocks; require dirname(__DIR__) . '/components/content-blocks.php'; ?></div></div></section>
<?php endif; ?>

<section class="section delivery-section" aria-labelledby="delivery-title">
    <div class="shell editorial-split"><div data-reveal><p class="eyebrow">APPROCHE DE RÉALISATION</p><h2 id="delivery-title">Réduire le risque avant d’augmenter le volume de code.</h2></div><ol class="delivery-list"><?php foreach ((array) ($solution['delivery'] ?? []) as $index => $item): ?><li data-reveal><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><p><?= e((string) $item) ?></p></li><?php endforeach; ?></ol></div>
</section>

<section class="section section--sage related-solutions" aria-labelledby="related-title">
    <div class="shell"><header class="section-heading"><div><p class="eyebrow">SOLUTIONS LIÉES</p><h2 id="related-title">Prolonger le système sans créer de rupture.</h2></div></header><div class="related-grid"><?php foreach ($relatedSlugs as $relatedSlug): ?><?php $related = $lang['financial_solutions'][$relatedSlug] ?? null; if (!is_array($related)) { continue; } ?><a href="<?= e($pathFor('solutions.show', ['slug' => $relatedSlug])) ?>"><span><?= e((string) $related['number']) ?></span><h3><?= e((string) $related['title']) ?></h3><p><?= e((string) $related['summary']) ?></p><b>Explorer →</b></a><?php endforeach; ?></div></div>
</section>

<?php
$ctaTitle = 'Votre logique ne devrait pas disparaître dans les limites d’un produit standard.';
$ctaCopy = 'Présentez-nous le modèle, les acteurs, les règles et les systèmes à relier. Nous cadrerons le bon point de départ.';
$ctaHref = $pathFor('project', [], ['solution' => $slug]);
require dirname(__DIR__) . '/components/cta-band.php';
?>

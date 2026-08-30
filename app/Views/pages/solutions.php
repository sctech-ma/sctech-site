<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$solutionRows = is_array($solutions ?? null) && $solutions !== [] ? $solutions : ($lang['financial_solutions'] ?? []);
$solutionIndex = [];
foreach ($solutionRows as $key => $solutionRow) {
    if (!is_array($solutionRow)) {
        continue;
    }
    $slug = (string) ($solutionRow['slug'] ?? (is_string($key) ? $key : ''));
    if (isset($lang['financial_solutions'][$slug])) {
        $solutionIndex[$slug] = array_replace($lang['financial_solutions'][$slug], $solutionRow);
    }
}
foreach (($lang['financial_solutions'] ?? []) as $slug => $fallback) {
    $solutionIndex[$slug] ??= $fallback;
}
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name, array $parameters = []) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name, $parameters);
    }
    return $name === 'project' ? '/demander-un-projet' : ($name === 'solutions.show' ? '/solutions/' . rawurlencode((string) ($parameters['slug'] ?? '')) : '/solutions');
};
$breadcrumbs = [['label' => 'Solutions', 'href' => $pathFor('solutions.index')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--solutions">
    <div class="shell page-intro__grid">
        <div data-reveal><p class="eyebrow">SOLUTIONS FINANCIÈRES SUR MESURE</p><h1>Le produit s’adapte à votre modèle. Pas l’inverse.</h1></div>
        <div data-reveal data-reveal-delay="1"><p>Chaque solution part d’un travail réel : qualifier, instruire, valider, intégrer, publier ou suivre. Elle est ensuite construite autour de vos rôles, de vos données et de vos règles.</p><a class="button button--forest" href="<?= e($pathFor('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a></div>
    </div>
    <div class="shell page-intro__index" aria-label="Cinq solutions"><span>01 Plateformes</span><span>02 Portails</span><span>03 Conformité</span><span>04 Data</span><span>05 Intégrations</span></div>
</header>

<section class="section solution-index" aria-labelledby="solution-index-title">
    <div class="shell">
        <header class="section-heading" data-reveal><div><p class="eyebrow">CINQ POINTS D’ENTRÉE</p><h2 id="solution-index-title">Une architecture financière ne se choisit pas sur catalogue.</h2></div><p>Ces familles structurent le cadrage. Le périmètre final dépend des acteurs, contrôles, systèmes existants et responsabilités propres à votre organisation.</p></header>
        <div class="solution-index__list">
            <?php foreach ($solutionIndex as $solution): ?>
                <article class="solution-index__item" data-reveal>
                    <div class="solution-index__visual"><?php $visualSlug = (string) $solution['slug']; $visualTitle = (string) $solution['title']; require dirname(__DIR__) . '/components/solution-visual.php'; ?></div>
                    <div class="solution-index__copy">
                        <span><?= e((string) $solution['number']) ?> / 05</span>
                        <p class="eyebrow"><?= e((string) $solution['eyebrow']) ?></p>
                        <h2><a href="<?= e($pathFor('solutions.show', ['slug' => $solution['slug']])) ?>"><?= e((string) $solution['title']) ?></a></h2>
                        <p><?= e((string) $solution['summary']) ?></p>
                        <div class="solution-index__details"><div><small>LOGIQUE CONFIGURABLE</small><ul><?php foreach (array_slice((array) $solution['configurable_logic'], 0, 3) as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?></ul></div><div><small>CAPACITÉS</small><ul><?php foreach (array_slice((array) $solution['capabilities'], 0, 3) as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?></ul></div></div>
                        <a class="text-link" href="<?= e($pathFor('solutions.show', ['slug' => $solution['slug']])) ?>">Découvrir le système <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--sage solution-assembly" aria-labelledby="assembly-title">
    <div class="shell editorial-split"><div data-reveal><p class="eyebrow">COMPOSER, PAS EMPILER</p><h2 id="assembly-title">Les frontières entre solutions restent traversables.</h2></div><div class="rule-stack" data-reveal><article><span>01</span><div><h3>Un portail peut déclencher un workflow</h3><p>L’expérience investisseur et le traitement interne partagent les mêmes états, sans doubles saisies.</p></div></article><article><span>02</span><div><h3>Une règle peut alimenter le reporting</h3><p>La qualification conserve la version du référentiel, les données utilisées et la décision humaine.</p></div></article><article><span>03</span><div><h3>Une intégration reste observable</h3><p>Chaque échange critique expose son état, ses erreurs, ses reprises et son responsable.</p></div></article></div></div>
</section>

<?php require dirname(__DIR__) . '/components/cta-band.php'; ?>

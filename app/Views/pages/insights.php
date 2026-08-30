<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$articles = is_array($articles ?? null) && $articles !== [] ? $articles : ($lang['insights'] ?? []);
$activeCategory = (string) ($activeCategory ?? 'tous');
$categories = [
    'tous' => 'Tous',
    'finance-islamique' => 'Finance islamique',
    'experience-investisseur' => 'Expérience investisseur',
    'conformite' => 'Conformité',
    'architecture-financiere' => 'Architecture',
    'data-reporting' => 'Data & reporting',
];
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name, array $query = []) use ($routeResolver): string {
    $path = $routeResolver !== null ? (string) $routeResolver($name) : '/insights';
    return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};
$breadcrumbs = [['label' => 'Insights', 'href' => $pathFor('insights.index')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--insights"><div class="shell page-intro__grid"><div data-reveal><p class="eyebrow">INSIGHTS SCTECH</p><h1>Formaliser avant d’automatiser.</h1></div><div data-reveal><p>Des analyses originales sur la façon de transformer règles financières, données, validations et responsabilités en produits explicables.</p></div></div></header>

<section class="section insights-index" aria-labelledby="insights-index-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">BIBLIOTHÈQUE</p><h2 id="insights-index-title">Des cadres de décision à mettre au travail.</h2></div><p>Aucun résultat client, rendement, volume ou approbation n’est utilisé comme argument éditorial.</p></header>
    <nav class="filter-nav" aria-label="Filtrer les insights"><?php foreach ($categories as $value => $label): ?><a href="<?= e($value === 'tous' ? $pathFor('insights.index') : $pathFor('insights.index', ['categorie' => $value])) ?>"<?= $activeCategory === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
    <?php if ($articles !== []): ?><div class="article-grid article-grid--index"><?php foreach ($articles as $article): ?><?php require dirname(__DIR__) . '/components/article-card.php'; ?><?php endforeach; ?></div><?php else: ?><div class="empty-state"><p class="eyebrow">AUCUN CONTENU DANS CE FILTRE</p><h2>Choisissez une autre catégorie.</h2><a class="button button--outline" href="<?= e($pathFor('insights.index')) ?>">Voir tous les insights</a></div><?php endif; ?>
</div></section>

<section class="section section--sage editorial-promise" aria-labelledby="editorial-promise-title"><div class="shell editorial-split"><div data-reveal><p class="eyebrow">LIGNE ÉDITORIALE</p><h2 id="editorial-promise-title">Partager ce qui aide à mieux cadrer.</h2></div><div class="prose-large" data-reveal><p>Nous publions des raisonnements, des méthodes et des questions d’architecture applicables. Les hypothèses restent nommées, les frontières de responsabilité explicites et la conformité n’est jamais présentée comme une garantie.</p><p>Les anciens articles techniques restent accessibles dans la bibliothèque sans prendre le pas sur la spécialisation financière.</p></div></div></section>

<?php
$ctaTitle = 'Une question d’architecture mérite d’être appliquée à votre contexte ?';
$ctaCopy = 'Présentez-nous les acteurs, les règles et la décision que le futur produit doit rendre possible.';
require dirname(__DIR__) . '/components/cta-band.php';
?>

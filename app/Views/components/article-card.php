<?php

declare(strict_types=1);

$articleSlug = (string) ($article['slug'] ?? '');
$articleHref = isset($routeUrl) && is_callable($routeUrl)
    ? (string) $routeUrl('insights.show', ['slug' => $articleSlug])
    : '/insights/' . rawurlencode($articleSlug);
$articleTitle = $article['title'] ?? '';
$articleExcerpt = $article['excerpt'] ?? ($article['summary'] ?? '');
$articleCategory = $article['category'] ?? 'Insight';
$articleReadingTime = $article['reading_time'] ?? ($article['readingTime'] ?? 'Lecture');
?>
<article class="article-card" data-reveal>
    <div class="article-card__folio" aria-hidden="true"><span>SCT / INSIGHT</span><i></i></div>
    <div class="article-card__meta">
        <span><?= e((string) $articleCategory) ?></span>
        <span><?= e((string) $articleReadingTime) ?></span>
    </div>
    <h3><a href="<?= e($articleHref) ?>"><?= e((string) $articleTitle) ?></a></h3>
    <p><?= e((string) $articleExcerpt) ?></p>
    <a class="text-link" href="<?= e($articleHref) ?>" aria-label="Lire l’analyse : <?= e((string) $articleTitle) ?>">Lire l’analyse <span aria-hidden="true">→</span></a>
</article>

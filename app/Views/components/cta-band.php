<?php

declare(strict_types=1);

$ctaKicker = $ctaKicker ?? 'VOTRE PROCHAIN SYSTÈME';
$ctaTitle = $ctaTitle ?? 'Votre modèle financier ne rentre pas dans une solution standard ?';
$ctaCopy = $ctaCopy ?? 'Construisons celle qui lui correspond — autour de vos processus, de vos règles et de vos responsabilités.';
$ctaLabel = $ctaLabel ?? 'Étudier votre projet';
$ctaHref = $ctaHref ?? (
    isset($routeUrl) && is_callable($routeUrl)
        ? (string) $routeUrl('project')
        : '/demander-un-projet'
);
?>
<section class="cta-band" aria-labelledby="cta-band-title"><div class="shell cta-band__inner">
    <p class="eyebrow eyebrow--mint"><?= e((string) $ctaKicker) ?></p>
    <div><h2 id="cta-band-title"><?= e((string) $ctaTitle) ?></h2><p><?= e((string) $ctaCopy) ?></p></div>
    <a class="button button--light" href="<?= e((string) $ctaHref) ?>"><?= e((string) $ctaLabel) ?> <span aria-hidden="true">↗</span></a>
</div></section>

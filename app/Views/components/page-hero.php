<?php

declare(strict_types=1);

$heroKicker = $heroKicker ?? 'SCTECH';
$heroTitle = $heroTitle ?? '';
$heroLead = $heroLead ?? '';
$heroCode = $heroCode ?? 'SIGNAL / SCTECH';
$heroAside = $heroAside ?? null;
$publishedPage = is_array($cmsPage ?? null) ? $cmsPage : [];
if (trim((string) ($publishedPage['eyebrow'] ?? '')) !== '') {
    $heroKicker = (string) $publishedPage['eyebrow'];
}
if (trim((string) ($publishedPage['title'] ?? '')) !== '') {
    $heroTitle = (string) $publishedPage['title'];
}
if (trim((string) ($publishedPage['summary'] ?? '')) !== '') {
    $heroLead = (string) $publishedPage['summary'];
}
?>
<section class="page-hero">
    <div class="page-hero__grid shell">
        <div class="page-hero__copy" data-reveal>
            <p class="section-kicker section-kicker--cyan"><?= e((string) $heroKicker) ?></p>
            <h1><?= e((string) $heroTitle) ?></h1>
            <p><?= e((string) $heroLead) ?></p>
        </div>
        <div class="page-hero__signal" aria-hidden="true" data-reveal data-reveal-delay="1">
            <span><?= e((string) $heroCode) ?></span>
            <div><i></i><i></i><i></i><i></i></div>
            <?php if ($heroAside !== null): ?><p><?= e((string) $heroAside) ?></p><?php endif; ?>
        </div>
    </div>
</section>

<?php

declare(strict_types=1);

$caseStudy = is_array($caseStudy ?? null) ? $caseStudy : [];
$caseTitle = trim((string) ($caseStudy['title'] ?? '')) ?: 'Réalisation SCTECH';
$caseSlug = (string) ($caseStudy['slug'] ?? '');
$caseSector = trim((string) ($caseStudy['sector'] ?? ''));
$caseSummary = trim((string) ($caseStudy['summary'] ?? ''));
$caseBlocks = is_array($caseStudy['blocks'] ?? null) ? $caseStudy['blocks'] : [];
$breadcrumbs = [
    ['label' => 'Réalisations', 'href' => '/realisations'],
    ['label' => $caseTitle, 'href' => '/realisations/' . $caseSlug],
];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<article class="case-study">
    <header class="case-study__hero">
        <div class="shell case-study__hero-grid">
            <div data-reveal>
                <p class="section-kicker section-kicker--cyan"><?= e($caseSector !== '' ? $caseSector : 'Réalisation vérifiée') ?></p>
                <h1><?= e($caseTitle) ?></h1>
                <?php if ($caseSummary !== ''): ?><p><?= e($caseSummary) ?></p><?php endif; ?>
            </div>
            <dl data-reveal data-reveal-delay="1">
                <?php if (!empty($caseStudy['duration'])): ?><div><dt>Durée</dt><dd><?= e((string) $caseStudy['duration']) ?></dd></div><?php endif; ?>
                <?php if ($caseSector !== ''): ?><div><dt>Secteur</dt><dd><?= e($caseSector) ?></dd></div><?php endif; ?>
                <?php if (!empty($caseStudy['scope'])): ?><div><dt>Intervention</dt><dd><?= e((string) $caseStudy['scope']) ?></dd></div><?php endif; ?>
                <div><dt>Publication</dt><dd>Contenu validé</dd></div>
            </dl>
        </div>
    </header>
    <?php if ($caseBlocks !== []): ?>
        <div class="shell case-study__body">
            <aside class="case-study__aside">
                <p class="section-kicker">Lecture du cas</p>
                <nav aria-label="Sections du cas"><a href="#contenu">Narratif validé</a></nav>
            </aside>
            <div class="case-study__content">
                <section id="contenu" aria-label="Contenu de la réalisation">
                    <?php
                    $blocks = $caseBlocks;
                    require dirname(__DIR__) . '/components/content-blocks.php';
                    ?>
                </section>
            </div>
        </div>
    <?php endif; ?>
</article>
<?php
$ctaTitle = 'Un contexte similaire ne produit pas forcément la même réponse.';
$ctaCopy = 'Parlons de vos systèmes, de vos contraintes et du niveau de résultat attendu.';
$ctaLabel = 'Échanger sur mon projet';
require dirname(__DIR__) . '/components/cta-band.php';
?>

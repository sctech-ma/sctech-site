<?php

declare(strict_types=1);

$publishedCases = array_values(array_filter(
    is_array($caseStudies ?? null) ? $caseStudies : [],
    static fn (array $case): bool => ($case['status'] ?? '') === 'published'
));
$breadcrumbs = [['label' => 'Réalisations', 'href' => '/realisations']];
require dirname(__DIR__) . '/components/breadcrumbs.php';
$heroKicker = 'Réalisations';
$heroTitle = 'Des interventions racontées avec précision — et seulement lorsqu’elles sont vérifiables.';
$heroLead = 'La confiance n’exige pas de sur-exposer les clients. SCTECH publie uniquement les contextes, approches et résultats autorisés, avec un périmètre explicite.';
$heroCode = 'PREUVE / CONTRÔLÉE';
$heroAside = 'Aucun client, chiffre ou résultat n’est inventé pour remplir cette page.';
require dirname(__DIR__) . '/components/page-hero.php';
?>
<section class="section realization-index" aria-labelledby="realization-index-title">
    <div class="shell">
        <header class="section-heading" data-reveal>
            <div><p class="section-kicker">Bibliothèque de cas</p><h2 id="realization-index-title">Le contexte avant le récit.</h2></div>
            <p>Les cas publiés peuvent être filtrés côté serveur par secteur lorsque des références approuvées sont disponibles.</p>
        </header>
        <?php if ($publishedCases !== []): ?>
            <div class="case-index-grid">
                <?php foreach ($publishedCases as $case): ?>
                    <article class="case-index-card" data-reveal>
                        <div class="case-index-card__visual" aria-hidden="true"><span><?= e((string) ($case['sector'] ?? 'Projet')) ?></span><i></i><i></i></div>
                        <div>
                            <p class="section-kicker"><?= e((string) ($case['sector'] ?? 'Réalisation')) ?></p>
                            <h3><a href="/realisations/<?= e((string) ($case['slug'] ?? '')) ?>"><?= e((string) ($case['title'] ?? 'Réalisation')) ?></a></h3>
                            <p><?= e((string) ($case['summary'] ?? '')) ?></p>
                            <?php if (!empty($case['technologies']) && is_array($case['technologies'])): ?>
                                <ul class="tag-list"><?php foreach ($case['technologies'] as $technology): ?><li><?= e((string) $technology) ?></li><?php endforeach; ?></ul>
                            <?php endif; ?>
                            <a class="text-link" href="/realisations/<?= e((string) ($case['slug'] ?? '')) ?>">Lire le cas <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="reference-vault" data-reveal>
                <div class="reference-vault__code" aria-hidden="true"><b>SCT / REF</b><span>ACCÈS CONTEXTUEL</span><i></i><i></i><i></i><i></i></div>
                <div>
                    <p class="section-kicker">Publication en attente de validation</p>
                    <h2>Pas de faux cas client. Pas de résultat sans source.</h2>
                    <p>Les réalisations actuellement enregistrées restent en brouillon tant que leur publication, leur niveau d’anonymisation et leurs résultats n’ont pas été validés.</p>
                    <p>Pour un besoin qualifié, demandez un échange confidentiel : nous présenterons uniquement les éléments que SCTECH est autorisée à partager.</p>
                    <a class="button button--ink" href="/contact?objet=references">Demander des références pertinentes <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--paper proof-framework" aria-labelledby="proof-framework-title">
    <div class="shell split-heading">
        <div data-reveal><p class="section-kicker">Ce qu’un cas doit documenter</p><h2 id="proof-framework-title">Une preuve utile dépasse le résultat final.</h2></div>
        <dl class="proof-definition" data-reveal>
            <div><dt>Contexte</dt><dd>Le système, le secteur et le problème, avec le niveau d’anonymisation autorisé.</dd></div>
            <div><dt>Intervention</dt><dd>Le périmètre exact de SCTECH, les dépendances et les responsabilités.</dd></div>
            <div><dt>Contraintes</dt><dd>Les exigences de sécurité, de données, de continuité et de délai.</dd></div>
            <div><dt>Résultat</dt><dd>Un changement vérifiable, documenté et validé pour publication.</dd></div>
        </dl>
    </div>
</section>

<?php
$ctaTitle = 'Vous souhaitez vérifier notre pertinence pour votre contexte ?';
$ctaCopy = 'Expliquez-nous le type de projet. Nous vous indiquerons quelles informations de référence peuvent être partagées.';
$ctaLabel = 'Demander un échange confidentiel';
require dirname(__DIR__) . '/components/cta-band.php';
?>

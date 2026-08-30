<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$steps = is_array($lang['method'] ?? null) ? $lang['method'] : [];
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name);
    }
    return $name === 'project' ? '/demander-un-projet' : '/approche';
};
$breadcrumbs = [['label' => 'Approche', 'href' => $pathFor('approach')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--approach"><div class="shell page-intro__grid"><div data-reveal><p class="eyebrow">APPROCHE SCTECH</p><h1>Faire progresser le produit sans perdre la logique.</h1></div><div data-reveal><p>Nous rendons les décisions structurantes visibles tôt, puis nous avançons par incréments vérifiables — du modèle métier jusqu’à l’exploitation.</p><a class="button button--forest" href="<?= e($pathFor('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a></div></div></header>

<section class="section approach-principle" aria-labelledby="approach-principle-title"><div class="shell manifesto__grid"><p class="eyebrow" data-reveal>UN CADRE, PAS UNE RECETTE</p><h2 id="approach-principle-title" data-reveal>Réduire l’incertitude au bon moment. <em>Conserver la capacité de décider.</em></h2><p data-reveal>Le plan s’adapte au contexte, mais chaque étape doit produire une compréhension ou une capacité réutilisable. Les hypothèses importantes deviennent testables avant de devenir coûteuses.</p></div></section>

<section class="section section--sage approach-steps" aria-labelledby="approach-steps-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">HUIT ÉTAPES</p><h2 id="approach-steps-title">Une progression de la compréhension vers l’autonomie.</h2></div><p>Les étapes peuvent se chevaucher, jamais disparaître. Chaque passage possède un objectif, une décision et une sortie observable.</p></header><ol class="approach-timeline">
    <?php foreach ($steps as $index => $step): ?>
        <?php $outputs = [
            ['Cartographie du contexte', 'Questions ouvertes', 'Critères de valeur'],
            ['Modèle métier', 'Catalogue de règles', 'Matrice des rôles'],
            ['Architecture cible', 'Contrats de données', 'Menaces et contrôles'],
            ['Prototype testable', 'Parcours critiques', 'Retours documentés'],
            ['Incréments démontrables', 'Tests automatisés', 'Documentation active'],
            ['Cas métier validés', 'Revue de sécurité', 'Décision de déploiement'],
            ['Runbooks et supervision', 'Plan de reprise', 'Transfert aux équipes'],
            ['Backlog d’évolution', 'Gouvernance des règles', 'Boucles d’observation'],
        ][$index] ?? []; ?>
        <li data-reveal><span><?= e((string) ($step['number'] ?? str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))) ?></span><div><p class="eyebrow">ÉTAPE <?= e((string) ($index + 1)) ?></p><h3><?= e((string) ($step['name'] ?? 'Étape')) ?></h3><p><?= e((string) ($step['copy'] ?? '')) ?></p></div><ul><?php foreach ($outputs as $output): ?><li><?= e($output) ?></li><?php endforeach; ?></ul></li>
    <?php endforeach; ?>
</ol></div></section>

<section class="workflow-band approach-decisions" aria-labelledby="approach-decisions-title"><div class="shell"><div data-reveal><p class="eyebrow eyebrow--mint">POINTS DE DÉCISION</p><h2 id="approach-decisions-title">À chaque étape, savoir ce qui est assez clair pour continuer.</h2><p>Une validation n’est pas un rituel. Elle confirme le périmètre, les responsabilités, les risques acceptés et la preuve attendue au prochain incrément.</p></div><ol><li data-reveal><span>01</span><strong>Modèle compris</strong></li><li data-reveal><span>02</span><strong>Règles formalisées</strong></li><li data-reveal><span>03</span><strong>Architecture défendable</strong></li><li data-reveal><span>04</span><strong>Produit exploitable</strong></li></ol></div></section>

<section class="section collaboration-section" aria-labelledby="collaboration-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">COLLABORATION</p><h2 id="collaboration-title">Un projet lisible pour les métiers comme pour la technique.</h2></div><p>Les artefacts ne servent pas à documenter après coup : ils permettent aux bonnes personnes de contester, valider et décider au bon moment.</p></header><div class="dual-list"><section data-reveal><span>A / VOTRE ÉQUIPE</span><ul><li>Porte la stratégie et les priorités</li><li>Désigne les autorités et responsables</li><li>Valide les règles et cas représentatifs</li><li>Prépare les données et accès nécessaires</li><li>Décide des arbitrages de risque</li></ul></section><section data-reveal><span>B / SCTECH</span><ul><li>Structure les questions et dépendances</li><li>Conçoit parcours, modèles et architecture</li><li>Construit et teste les incréments</li><li>Rend les risques et compromis visibles</li><li>Prépare l’exploitation et le transfert</li></ul></section></div></div></section>

<?php require dirname(__DIR__) . '/components/cta-band.php'; ?>

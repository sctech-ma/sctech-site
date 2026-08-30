<?php

declare(strict_types=1);

$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name);
    }
    return $name === 'project' ? '/demander-un-projet' : ($name === 'solutions.index' ? '/solutions' : '/expertise');
};
$breadcrumbs = [['label' => 'Expertise', 'href' => $pathFor('expertise')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--expertise">
    <div class="shell page-intro__grid"><div data-reveal><p class="eyebrow">EXPERTISE PRODUIT · FINANCE · INGÉNIERIE</p><h1>Construire la logique financière de bout en bout.</h1></div><div data-reveal><p>Nous relions compréhension métier, conception produit, architecture logicielle, données et sécurité pour que le système final reste fidèle aux décisions qu’il doit servir.</p><a class="button button--forest" href="<?= e($pathFor('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a></div></div>
</header>

<section class="section expertise-principle" aria-labelledby="expertise-principle-title">
    <div class="shell manifesto__grid"><p class="eyebrow" data-reveal>NOTRE DISCIPLINE</p><h2 id="expertise-principle-title" data-reveal>Un produit financier ne se résume ni à son interface, ni à son code. <em>Il matérialise une gouvernance.</em></h2><p data-reveal>Chaque champ, statut, permission et automatisation doit pouvoir être relié à une responsabilité réelle. C’est cette continuité qui guide la conception.</p></div>
</section>

<section class="section section--sage expertise-pillars" aria-labelledby="pillars-title">
    <div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">QUATRE COUCHES INDISSOCIABLES</p><h2 id="pillars-title">La précision à chaque frontière.</h2></div><p>Nous augmentons ou réduisons la profondeur de chaque couche selon le risque, les dépendances et le niveau de maturité du projet.</p></header>
        <div class="pillar-grid">
            <article data-reveal><span>01 / MODÈLE MÉTIER</span><h3>Formalisation financière</h3><p>Acteurs, objets, événements, décisions, règles, exceptions et preuves sont exprimés dans un langage partagé.</p><ul><li>Domain mapping</li><li>Catalogue de règles</li><li>Matrice de responsabilités</li><li>Cas limites</li></ul></article>
            <article data-reveal><span>02 / PRODUIT</span><h3>Expérience opérable</h3><p>Les parcours guident sans masquer l’état du dossier, la prochaine décision ou la raison d’un blocage.</p><ul><li>Service blueprint</li><li>Prototypes métier</li><li>Back-office et self-service</li><li>Accessibilité</li></ul></article>
            <article data-reveal><span>03 / INGÉNIERIE</span><h3>Architecture sécurisée</h3><p>Les composants, permissions et contrats d’échange sont conçus pour évoluer sans rendre le risque invisible.</p><ul><li>Architecture applicative</li><li>RBAC et séparation des rôles</li><li>Secure SDLC</li><li>Observabilité</li></ul></article>
            <article data-reveal><span>04 / DATA</span><h3>Données explicables</h3><p>Les sources, transformations, contrôles et versions utilisées restent identifiables jusqu’à la décision.</p><ul><li>Modèle canonique</li><li>Qualité et lignage</li><li>Reporting</li><li>Intégrations API</li></ul></article>
        </div>
    </div>
</section>

<section class="section operating-model" aria-labelledby="operating-model-title">
    <div class="shell editorial-split"><div data-reveal><p class="eyebrow">CONTINUITÉ DE CONCEPTION</p><h2 id="operating-model-title">Une décision traverse tout le système.</h2><p>Une exigence n’est réellement intégrée que lorsque ses données, son contrôle, son interface, son autorité et sa trace racontent la même chose.</p></div><div class="operating-model__diagram" data-reveal aria-label="Chaîne de conception"><span>Métier</span><i></i><span>Règles</span><i></i><span>Produit</span><i></i><span>Architecture</span><i></i><span>Opérations</span></div></div>
</section>

<section class="architecture-rail" aria-labelledby="engineering-title">
    <div class="shell architecture-rail__grid"><div data-reveal><p class="eyebrow eyebrow--mint">ENGINEERING STANDARDS</p><h2 id="engineering-title">Un socle conçu pour être repris, observé et maintenu.</h2><p>Les choix restent proportionnés au produit. L’objectif n’est pas la sophistication : c’est une architecture dont les compromis sont explicites et les opérations préparées.</p></div><ul data-reveal><li><span>01</span>Composants modulaires</li><li><span>02</span>Contrats d’API versionnés</li><li><span>03</span>Contrôles automatisés</li><li><span>04</span>Journalisation corrélée</li><li><span>05</span>Documentation vivante</li><li><span>06</span>Déploiements reproductibles</li></ul></div>
</section>

<section class="section deliverable-section" aria-labelledby="deliverables-title">
    <div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">LIVRABLES UTILES</p><h2 id="deliverables-title">Ce que le projet laisse aux équipes.</h2></div><p>La transmission est prévue dès le cadrage, pas ajoutée à la fin lorsque la connaissance est déjà concentrée.</p></header><div class="possibility-grid"><article data-reveal><span>01</span><h3>Décisions documentées</h3><p>Hypothèses, contraintes, arbitrages et responsabilités compréhensibles.</p></article><article data-reveal><span>02</span><h3>Système exploitable</h3><p>Observabilité, procédures, reprises et conditions d’escalade préparées.</p></article><article data-reveal><span>03</span><h3>Règles administrables</h3><p>Les éléments destinés à évoluer sont séparés du code lorsque c’est sûr et pertinent.</p></article><article data-reveal><span>04</span><h3>Capacité transférée</h3><p>Documentation, démonstrations, formation et responsabilités de maintenance.</p></article></div></div>
</section>

<section class="section section--sage expertise-next" aria-labelledby="expertise-next-title"><div class="shell editorial-split"><div data-reveal><p class="eyebrow">PARTIR DU BESOIN</p><h2 id="expertise-next-title">Voir comment cette expertise prend forme dans le produit.</h2></div><div data-reveal><p>Les cinq familles de solutions montrent comment les capacités se combinent autour d’un workflow financier concret.</p><a class="button button--outline" href="<?= e($pathFor('solutions.index')) ?>">Explorer les solutions <span aria-hidden="true">→</span></a></div></div></section>

<?php require dirname(__DIR__) . '/components/cta-band.php'; ?>

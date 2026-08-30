<?php

declare(strict_types=1);

$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$pathFor = static function (string $name) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name);
    }
    return match ($name) { 'project' => '/demander-un-projet', 'expertise' => '/expertise', default => '/a-propos' };
};
$breadcrumbs = [['label' => 'À propos', 'href' => $pathFor('about')]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<header class="page-intro page-intro--about"><div class="shell page-intro__grid"><div data-reveal><p class="eyebrow">SCTECH · CASABLANCA</p><h1>Une équipe de conception au service des logiques financières exigeantes.</h1></div><div data-reveal><p>SCTECH développe des logiciels financiers sur mesure depuis le Maroc, avec une approche pensée pour collaborer avec des organisations en Afrique et en Europe.</p><a class="button button--forest" href="<?= e($pathFor('project')) ?>">Parler de votre projet <span aria-hidden="true">↗</span></a></div></div></header>

<section class="section about-position" aria-labelledby="about-position-title"><div class="shell manifesto__grid"><p class="eyebrow" data-reveal>NOTRE POSITION</p><h2 id="about-position-title" data-reveal>Les modèles financiers différenciants méritent mieux qu’un assemblage de contournements.</h2><p data-reveal>Notre travail consiste à comprendre la logique propre à chaque organisation, puis à la transformer en un produit lisible, sécurisé et maintenable. Nous ne revendiquons aucune certification, client, ancienneté ou résultat qui ne soit vérifié.</p></div></section>

<section class="section section--sage about-principles" aria-labelledby="about-principles-title"><div class="shell"><header class="section-heading" data-reveal><div><p class="eyebrow">PRINCIPES DE TRAVAIL</p><h2 id="about-principles-title">La confiance se construit dans les détails vérifiables.</h2></div></header><div class="pillar-grid"><article data-reveal><span>01 / CLARTÉ</span><h3>Dire ce que le système sait — et ce qu’il ne sait pas.</h3><p>Les états indéterminés, exceptions et responsabilités humaines restent visibles.</p></article><article data-reveal><span>02 / PROPRIÉTÉ</span><h3>Vous rendre la maîtrise du produit.</h3><p>Architecture, documentation et transfert limitent la dépendance à une connaissance implicite.</p></article><article data-reveal><span>03 / PRUDENCE</span><h3>Ne pas transformer un objectif en promesse.</h3><p>Performance, conformité et résultats sont évalués dans le périmètre réel, jamais proclamés.</p></article><article data-reveal><span>04 / CONTINUITÉ</span><h3>Penser conception et exploitation ensemble.</h3><p>La reprise, l’observation et l’évolution font partie du produit dès le départ.</p></article></div></div></section>

<section class="architecture-rail about-location" aria-labelledby="location-title"><div class="shell architecture-rail__grid"><div data-reveal><p class="eyebrow eyebrow--mint">MAROC · AFRIQUE · EUROPE</p><h2 id="location-title">Une base à Casablanca. Une collaboration conçue pour traverser les contextes.</h2><p>Nous organisons les décisions, démonstrations, revues et documents pour rendre le travail compréhensible à distance comme en proximité.</p></div><ul data-reveal><li><span>01</span>Communication française d’abord</li><li><span>02</span>Décisions documentées</li><li><span>03</span>Rituels adaptés au projet</li><li><span>04</span>Contexte local, exigence internationale</li></ul></div></section>

<section class="section about-boundaries" aria-labelledby="boundaries-title"><div class="shell editorial-split"><div data-reveal><p class="eyebrow">RESPONSABILITÉ</p><h2 id="boundaries-title">Une spécialisation technique, avec des frontières claires.</h2></div><div class="prose-large" data-reveal><p>SCTECH conçoit et développe des systèmes logiciels. Les choix financiers, avis religieux, critères de conformité, décisions d’investissement et certifications relèvent des personnes ou instances compétentes désignées par le client.</p><p>Notre rôle est de traduire leurs exigences validées en modèles, règles, contrôles, parcours et traces — puis de rendre cette traduction testable.</p></div></div></section>

<section class="section section--sage about-next" aria-labelledby="about-next-title"><div class="shell editorial-split"><div data-reveal><p class="eyebrow">NOTRE SAVOIR-FAIRE</p><h2 id="about-next-title">Voir comment finance, produit et ingénierie se rejoignent.</h2></div><div data-reveal><p>Notre page Expertise détaille les quatre couches mobilisées pour construire un produit financier exploitable.</p><a class="button button--outline" href="<?= e($pathFor('expertise')) ?>">Découvrir l’expertise <span aria-hidden="true">→</span></a></div></div></section>

<?php require dirname(__DIR__) . '/components/cta-band.php'; ?>

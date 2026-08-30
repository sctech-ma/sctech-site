<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$article = is_array($article ?? null) ? $article : [];
$requestedSlug = (string) ($slug ?? ($article['slug'] ?? ''));
$fallbackArticle = null;
foreach (($lang['insights'] ?? []) as $candidate) {
    if (($candidate['slug'] ?? null) === $requestedSlug) {
        $fallbackArticle = $candidate;
        break;
    }
}
$article = array_replace(is_array($fallbackArticle) ? $fallbackArticle : [], $article);
$defaultBodies = [
    'referentiel-moteur-regles-conformite-charia' => [
        ['type' => 'paragraph', 'text' => 'Un référentiel validé exprime des principes et des critères. Un produit doit, lui, traiter des données parfois incomplètes, des exceptions, des versions et des responsabilités. La formalisation relie ces deux mondes sans transférer l’autorité au logiciel.'],
        ['type' => 'heading', 'text' => 'Commencer par l’autorité et le périmètre'],
        ['type' => 'paragraph', 'text' => 'Pour chaque exigence, identifiez qui la définit, qui peut l’interpréter, quels produits ou opérations elle concerne et à partir de quelle date elle s’applique. Cette frontière évite qu’une règle technique soit confondue avec un avis religieux.'],
        ['type' => 'list', 'title' => 'Une fiche de règle utile précise', 'items' => ['La source et la version du référentiel.', 'Les données nécessaires et leur origine.', 'Les conditions, seuils et unités.', 'Les cas indéterminés ou réservés à une revue.', 'Le rôle autorisé à conclure.', 'La preuve à conserver avec la décision.']],
        ['type' => 'heading', 'text' => 'Concevoir trois issues, pas seulement deux'],
        ['type' => 'paragraph', 'text' => 'Conforme ou non conforme ne suffit pas lorsque la donnée manque, que plusieurs interprétations restent possibles ou qu’une exception a été prévue. Un état de revue explicite protège mieux la gouvernance qu’une conclusion automatique forcée.'],
        ['type' => 'callout', 'label' => 'Responsabilité', 'text' => 'Le moteur exécute une formalisation approuvée. Les instances compétentes du client restent responsables des critères et de leur validation.'],
        ['type' => 'heading', 'text' => 'Versionner règle, cas de test et décision'],
        ['type' => 'paragraph', 'text' => 'Une évolution ne doit pas réécrire silencieusement le passé. Chaque qualification conserve la version appliquée, tandis que des cas représentatifs testent le comportement avant activation.'],
    ],
    'portail-investisseur-onboarding-validations-tracabilite' => [
        ['type' => 'paragraph', 'text' => 'Un portail investisseur est souvent évalué par son interface. Sa fiabilité dépend pourtant de ce qu’il orchestre derrière chaque écran : documents attendus, vérifications, responsabilités, relances, décisions et synchronisation avec les systèmes internes.'],
        ['type' => 'heading', 'text' => 'Dessiner le parcours avec son envers opérationnel'],
        ['type' => 'paragraph', 'text' => 'Pour chaque action investisseur, décrivez le travail déclenché côté back-office. Un document envoyé doit avoir un propriétaire, un état de contrôle, un délai et une voie de correction compréhensible.'],
        ['type' => 'list', 'items' => ['Le statut visible et sa signification précise.', 'Le rôle qui attend une action.', 'La donnée ou pièce qui manque.', 'Le prochain état autorisé.', 'La trace conservée après la transition.']],
        ['type' => 'heading', 'text' => 'Éviter les statuts décoratifs'],
        ['type' => 'paragraph', 'text' => 'Un statut comme en cours devient vite ambigu : traitement commencé, file d’attente, blocage externe ou décision attendue ? Des états trop génériques rendent les relances imprécises et les indicateurs peu fiables.'],
        ['type' => 'callout', 'label' => 'Question de conception', 'text' => 'Un investisseur peut-il comprendre ce qui est attendu sans que l’interface révèle une information interne ou sensible ?'],
        ['type' => 'heading', 'text' => 'Relier expérience et preuve'],
        ['type' => 'paragraph', 'text' => 'La bonne traçabilité n’est pas une copie de tous les clics. Elle capture les événements qui expliquent une décision : version du document, identité du rôle, contrôle exécuté, réserve et validation finale.'],
    ],
    'screening-financier-qualification-explicable-auditable' => [
        ['type' => 'paragraph', 'text' => 'Un screening financier utile ne produit pas seulement un statut. Il permet de comprendre quelles données ont été utilisées, comment elles ont été transformées, quelle règle a été appliquée et pourquoi une revue humaine a été demandée.'],
        ['type' => 'heading', 'text' => 'Séparer source, calcul et décision'],
        ['type' => 'paragraph', 'text' => 'La donnée brute, l’indicateur calculé et la conclusion sont trois objets différents. Les confondre empêche de corriger une source, de faire évoluer une méthode de calcul ou de rejouer proprement une qualification.'],
        ['type' => 'steps', 'items' => ['Identifier la source et sa date de validité.', 'Normaliser les unités et périodes.', 'Calculer avec une formule versionnée.', 'Appliquer la règle et ses seuils.', 'Router les exceptions vers le bon rôle.', 'Conserver les entrées et la décision.']],
        ['type' => 'heading', 'text' => 'Prévoir l’incertitude comme un état normal'],
        ['type' => 'paragraph', 'text' => 'Une source absente, contradictoire ou trop ancienne ne doit pas devenir une valeur arbitraire. Le système peut qualifier la donnée, bloquer le calcul concerné et demander un complément ou une décision manuelle.'],
        ['type' => 'callout', 'label' => 'Auditabilité', 'text' => 'Reproduire un résultat exige les mêmes entrées, la même version de formule et la même version de règle — pas seulement le statut final.'],
        ['type' => 'heading', 'text' => 'Faire évoluer sans casser la comparabilité'],
        ['type' => 'paragraph', 'text' => 'Lorsque les seuils changent, les nouvelles qualifications peuvent utiliser la version active tandis que l’historique reste attaché à sa logique d’origine. Un recalcul éventuel doit être explicite et daté.'],
    ],
];
$blocks = $article['blocks'] ?? ($article['content_blocks'] ?? ($article['content_json'] ?? null));
if (is_string($blocks)) {
    $decoded = json_decode($blocks, true);
    $blocks = is_array($decoded) ? ($decoded['blocks'] ?? $decoded) : [];
}
if (!is_array($blocks) || $blocks === []) {
    $blocks = $defaultBodies[$requestedSlug] ?? [
        ['type' => 'paragraph', 'text' => (string) ($article['excerpt'] ?? $article['summary'] ?? '')],
        ['type' => 'callout', 'label' => 'Note éditoriale', 'text' => 'Cette analyse ne présente aucun résultat client ni garantie de conformité.'],
    ];
}
$insightsPath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('insights.index') : '/insights';
$articlePath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('insights.show', ['slug' => $requestedSlug]) : '/insights/' . rawurlencode($requestedSlug);
$projectPath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('project', [], ['source' => 'insight']) : '/demander-un-projet?source=insight';
$breadcrumbs = [['label' => 'Insights', 'href' => $insightsPath], ['label' => (string) ($article['title'] ?? 'Article'), 'href' => $articlePath]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<article class="insight-article">
    <header class="insight-article__header"><div class="shell insight-article__header-grid"><div data-reveal><p class="eyebrow"><?= e((string) ($article['category'] ?? 'Insight financier')) ?></p><h1><?= e((string) ($article['title'] ?? 'Insight SCTECH')) ?></h1><p><?= e((string) ($article['excerpt'] ?? $article['summary'] ?? '')) ?></p></div><dl data-reveal data-reveal-delay="1"><div><dt>Lecture</dt><dd><?= e((string) ($article['reading_time'] ?? '8 min')) ?></dd></div><?php if (!empty($article['published_label'])): ?><div><dt>Publié</dt><dd><time datetime="<?= e((string) ($article['published_iso'] ?? '')) ?>"><?= e((string) $article['published_label']) ?></time></dd></div><?php endif; ?><div><dt>Édition</dt><dd>SCTECH</dd></div></dl></div></header>
    <div class="shell insight-article__body"><aside class="insight-article__aside"><p>NOTE / <?= e(strtoupper((string) ($article['category'] ?? 'FINANCE'))) ?></p><span>Cadre général.<br>À adapter au contexte.</span><a href="mailto:?subject=<?= e(rawurlencode((string) ($article['title'] ?? 'Insight SCTECH'))) ?>">Partager par e-mail</a></aside><div class="insight-article__content"><?php require dirname(__DIR__) . '/components/content-blocks.php'; ?></div></div>
</article>
<section class="section section--sage article-next" aria-labelledby="article-next-title"><div class="shell editorial-split"><div data-reveal><p class="eyebrow">APPLIQUER LE RAISONNEMENT</p><h2 id="article-next-title">Votre contexte change les règles du système.</h2></div><div data-reveal><p>Un article généralise. Votre produit doit préciser les acteurs, données, critères et responsabilités réellement concernés.</p><a class="button button--forest" href="<?= e($projectPath) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a></div></div></section>

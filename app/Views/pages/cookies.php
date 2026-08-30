<?php

declare(strict_types=1);

$legalReady = ($legalReady ?? false) === true;
$breadcrumbs = [['label' => 'Politique cookies', 'href' => '/politique-cookies']];
require dirname(__DIR__) . '/components/breadcrumbs.php';
$heroKicker = 'Cookies & stockage local';
$heroTitle = 'Une utilisation limitée aux fonctions essentielles.';
$heroLead = 'La version initiale du site n’intègre ni mesure d’audience, ni publicité, ni contenu tiers nécessitant des cookies optionnels.';
$heroCode = 'COOKIES / ESSENTIELS';
require dirname(__DIR__) . '/components/page-hero.php';
?>
<?php if (!$legalReady): ?>
    <div class="legal-status"><div class="shell"><strong>Version de préparation — validation requise</strong><p>La configuration des sessions et le texte final doivent être revus avant le lancement.</p></div></div>
<?php endif; ?>
<article class="section legal-page">
    <div class="shell legal-page__grid">
        <aside><p class="section-kicker">Navigation</p><nav aria-label="Sections cookies"><a href="#essentiels">Essentiels</a><a href="#liste">Liste</a><a href="#choix">Vos choix</a><a href="#evolution">Évolution</a></nav></aside>
        <div class="legal-page__content">
            <section id="essentiels"><h2>Fonctions essentielles</h2><p>Le site peut utiliser un identifiant de session strictement nécessaire à la protection des formulaires, à l’affichage temporaire d’une confirmation ou d’une erreur, et à l’authentification de l’espace d’administration. Ces fonctions ne servent pas à suivre votre navigation à des fins publicitaires.</p></section>
            <section id="liste"><h2>Stockage utilisé</h2><div class="table-wrap" tabindex="0"><table><thead><tr><th>Élément</th><th>Finalité</th><th>Portée</th></tr></thead><tbody><tr><td>Session publique</td><td>Protection CSRF et retour temporaire des formulaires</td><td>Session / durée technique configurée</td></tr><tr><td>Session administration</td><td>Authentification et sécurité de l’espace privé</td><td>Utilisateurs autorisés uniquement</td></tr></tbody></table></div><p>Le nom exact et la durée technique des cookies dépendent de la configuration de production et seront vérifiés avant lancement.</p></section>
            <section id="choix"><h2>Vos choix</h2><p>Comme aucun cookie optionnel n’est activé dans cette version, aucun bandeau de consentement marketing ou analytique n’est affiché. Vous pouvez configurer votre navigateur pour refuser ou supprimer les cookies, mais certaines fonctions de formulaire ou d’administration peuvent alors ne plus fonctionner.</p></section>
            <section id="evolution"><h2>Évolution du site</h2><p>L’ajout futur d’un outil de mesure d’audience, d’un contenu embarqué ou d’un service optionnel nécessitera une analyse séparée et, lorsque requis, une interface de consentement avant activation.</p></section>
            <section><h2>Question</h2><p>Pour toute question sur le stockage utilisé par le site, contactez <a href="mailto:contact@sctech.ma">contact@sctech.ma</a>.</p></section>
        </div>
    </div>
</article>

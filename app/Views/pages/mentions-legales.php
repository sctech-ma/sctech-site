<?php

declare(strict_types=1);

$legal = is_array($legal ?? null) ? $legal : [];
$legalReady = ($legalReady ?? false) === true;
$breadcrumbs = [['label' => 'Mentions légales', 'href' => '/mentions-legales']];
require dirname(__DIR__) . '/components/breadcrumbs.php';
$heroKicker = 'Informations légales';
$heroTitle = 'Mentions légales';
$heroLead = $legalReady
    ? 'Les informations d’identification, de publication et d’hébergement validées pour le site SCTECH.'
    : 'Cette page est préparée pour le lancement. Les informations d’identification manquantes doivent être complétées et validées avant publication en production.';
$heroCode = $legalReady ? 'DOCUMENT / VALIDÉ' : 'DOCUMENT / À VALIDER';
require dirname(__DIR__) . '/components/page-hero.php';
?>
<?php if (!$legalReady): ?>
    <div class="legal-status"><div class="shell"><strong>Version de préparation — noindex</strong><p>L’identité juridique, les identifiants officiels, l’adresse complète et les informations d’hébergement doivent être confirmés par SCTECH.</p></div></div>
<?php endif; ?>
<article class="section legal-page">
    <div class="shell legal-page__grid">
        <aside><p class="section-kicker">Navigation</p><nav aria-label="Sections des mentions légales"><a href="#editeur">Éditeur</a><a href="#direction">Direction</a><a href="#hebergement">Hébergement</a><a href="#propriete">Propriété intellectuelle</a><a href="#responsabilite">Responsabilité</a></nav></aside>
        <div class="legal-page__content">
            <section id="editeur"><h2>Éditeur du site</h2><dl><div><dt>Nom commercial</dt><dd>SCTECH</dd></div><div><dt>Entité juridique</dt><dd><?= e((string) ($legal['entity_name'] ?? 'À compléter avant lancement')) ?></dd></div><div><dt>Forme juridique</dt><dd><?= e((string) ($legal['legal_form'] ?? 'À compléter avant lancement')) ?></dd></div><div><dt>RC / ICE / IF</dt><dd><?= e((string) ($legal['registration_ids'] ?? 'À compléter avant lancement')) ?></dd></div><div><dt>Siège social</dt><dd><?= e((string) ($legal['full_address'] ?? 'Casablanca, Maroc — adresse complète à confirmer')) ?></dd></div><div><dt>Contact</dt><dd><a href="mailto:contact@sctech.ma">contact@sctech.ma</a><?php if ($legalReady): ?><br><?= e((string) $legal['phone']) ?><br><a href="<?= e((string) $legal['linkedin']) ?>" rel="noopener noreferrer">Profil LinkedIn officiel</a><?php endif; ?></dd></div></dl></section>
            <section id="direction"><h2>Direction de la publication</h2><p><?= e((string) ($legal['publication_director'] ?? 'À compléter avant lancement.')) ?></p></section>
            <section id="hebergement"><h2>Hébergement</h2><p><?= e((string) ($legal['hosting'] ?? 'Prestataire, adresse et coordonnées à compléter après confirmation de l’hébergement de production.')) ?></p></section>
            <section id="propriete"><h2>Propriété intellectuelle</h2><p>Les textes, éléments graphiques, interfaces et contenus de ce site sont protégés par les règles applicables en matière de propriété intellectuelle, sous réserve des droits détenus par leurs auteurs ou concédants respectifs. Toute réutilisation au-delà des exceptions légales nécessite une autorisation préalable.</p></section>
            <section id="responsabilite"><h2>Responsabilité</h2><p>SCTECH s’efforce de maintenir des informations exactes et accessibles. Les contenus sont fournis à titre informatif et ne constituent ni une certification, ni un avis juridique, ni une garantie de résultat ou de conformité. Les modalités d’une intervention sont définies dans un accord distinct.</p></section>
            <section><h2>Contact</h2><p>Pour signaler une erreur ou exercer une demande liée au site, écrivez à <a href="mailto:contact@sctech.ma">contact@sctech.ma</a>.</p></section>
        </div>
    </div>
</article>

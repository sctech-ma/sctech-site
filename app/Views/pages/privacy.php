<?php

declare(strict_types=1);

$legal = is_array($legal ?? null) ? $legal : [];
$legalReady = ($legalReady ?? false) === true;
$breadcrumbs = [['label' => 'Politique de confidentialité', 'href' => '/politique-confidentialite']];
require dirname(__DIR__) . '/components/breadcrumbs.php';
$heroKicker = 'Vie privée';
$heroTitle = 'Politique de confidentialité';
$heroLead = 'Une explication claire des données nécessaires au traitement des demandes envoyées à SCTECH.';
$heroCode = 'DONNÉES / MINIMISATION';
require dirname(__DIR__) . '/components/page-hero.php';
?>
<?php if (!$legalReady): ?>
    <div class="legal-status"><div class="shell"><strong>Version de préparation — validation requise</strong><p>La durée de conservation, l’identité juridique du responsable et le texte final doivent être approuvés avant le lancement.</p></div></div>
<?php endif; ?>
<article class="section legal-page">
    <div class="shell legal-page__grid">
        <aside><p class="section-kicker">Navigation</p><nav aria-label="Sections de confidentialité"><a href="#responsable">Responsable</a><a href="#donnees">Données</a><a href="#finalites">Finalités</a><a href="#conservation">Conservation</a><a href="#droits">Vos droits</a><a href="#securite">Sécurité</a></nav></aside>
        <div class="legal-page__content">
            <section id="responsable"><h2>Responsable du traitement</h2><p>SCTECH — <?= e((string) ($legal['entity_name'] ?? 'identité juridique à compléter')) ?>, Casablanca, Maroc. Contact : <a href="mailto:contact@sctech.ma">contact@sctech.ma</a>.</p></section>
            <section id="donnees"><h2>Données concernées</h2><p>Les formulaires peuvent recueillir votre nom, adresse e-mail, organisation, téléphone facultatif, sujet, description du besoin, expertise recherchée, enveloppe indicative et horizon. Des données techniques de sécurité peuvent également être traitées sous forme limitée ou pseudonymisée pour prévenir les abus.</p><p>N’envoyez pas de mot de passe, secret d’accès, donnée bancaire, document d’identité ou donnée personnelle qui n’est pas nécessaire à votre demande.</p></section>
            <section id="finalites"><h2>Pourquoi ces données sont utilisées</h2><ul><li>Recevoir, qualifier et répondre à une demande de contact ou de projet.</li><li>Préparer un échange et assurer le suivi de la demande.</li><li>Protéger les formulaires contre les envois abusifs et assurer la sécurité du site.</li><li>Conserver la preuve du consentement associé à la demande.</li></ul><p>Le consentement donné dans le formulaire concerne le traitement de cette demande. Le site ne l’utilise pas comme une inscription automatique à une communication marketing.</p></section>
            <section id="destinataires"><h2>Destinataires et prestataires</h2><p>L’accès aux demandes est limité aux personnes habilitées chez SCTECH. L’hébergeur, le fournisseur de messagerie ou d’autres prestataires techniques peuvent traiter les données nécessaires au service selon leur rôle et les accords applicables. La liste définitive doit être confirmée avec l’environnement de production.</p></section>
            <section id="conservation"><h2>Durée de conservation</h2><p><?= e((string) ($legal['retention_policy'] ?? 'La durée définitive reste à valider avant le lancement. Les données ne doivent pas être conservées au-delà de ce qui est nécessaire au traitement et au suivi légitime de la demande.')) ?></p></section>
            <section id="droits"><h2>Vos droits</h2><p>Selon les règles applicables à votre situation, vous pouvez demander l’accès, la rectification, l’effacement, la limitation ou l’opposition au traitement de vos données. Adressez votre demande à <a href="mailto:contact@sctech.ma">contact@sctech.ma</a> en donnant les informations nécessaires pour l’identifier, sans envoyer spontanément de document d’identité.</p><p>Les modalités finales et l’autorité de contrôle compétente doivent être confirmées lors de la revue juridique.</p></section>
            <section id="securite"><h2>Sécurité</h2><p>Le site applique des mesures destinées à limiter l’accès, les abus et l’exposition des données. Aucun système ne peut garantir un risque nul ; les incidents sont traités selon le contexte et les obligations applicables.</p></section>
            <section><h2>Mise à jour</h2><p><?= $legalReady ? 'Version applicable : ' . e((string) $legal['policy_version']) . '.' : 'La date et la version de la politique seront renseignées au moment de sa validation finale.' ?></p></section>
        </div>
    </div>
</article>

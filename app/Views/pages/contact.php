<?php

declare(strict_types=1);

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$csrfToken = (string) ($csrfToken ?? '');
$timingToken = (string) ($timingToken ?? ($formTimingToken ?? ''));
$idempotencyToken = (string) ($idempotencyToken ?? '');
$value = static fn (string $key): string => isset($old[$key]) && is_scalar($old[$key]) ? (string) $old[$key] : '';
$hasError = static fn (string $key): bool => isset($errors[$key]);
$errorText = static function (string $key) use ($errors): string {
    $error = $errors[$key] ?? '';
    return is_array($error) ? (string) reset($error) : (string) $error;
};
$subjects = [
    'software' => 'Produit financier sur mesure',
    'data-ai' => 'Data & reporting financier',
    'cybersecurity' => 'Workflow de conformité',
    'cloud' => 'Intégrations financières',
    'transformation' => 'Finance islamique — logique logicielle',
    'partnership' => 'Collaboration ou autre projet',
    'other' => 'Autre sujet',
];
$contactPath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('contact') : '/contact';
$projectPath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('project') : '/demander-un-projet';
$privacyPath = isset($routeUrl) && is_callable($routeUrl) ? (string) $routeUrl('privacy') : '/politique-confidentialite';
$breadcrumbs = [['label' => 'Contact', 'href' => $contactPath]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<section class="contact-hero">
    <div class="shell contact-hero__grid">
        <div data-reveal>
            <p class="eyebrow">CONTACT</p>
            <h1>Commençons par votre logique métier.</h1>
            <p>Décrivez le produit, le workflow ou la décision financière à outiller. SCTECH qualifiera le contexte et proposera un prochain pas adapté.</p>
        </div>
        <div class="contact-coordinate" data-reveal data-reveal-delay="1">
            <span>POINT DE CONTACT / SCTECH</span>
            <a href="mailto:contact@sctech.ma">contact@sctech.ma</a>
            <p>Casablanca, Maroc<br>Collaboration Maroc · Afrique · Europe</p>
        </div>
    </div>
</section>

<section class="section contact-section" aria-labelledby="contact-form-title">
    <div class="shell form-layout">
        <aside class="form-layout__aside" data-reveal>
            <p class="section-kicker">Ce qui nous aide</p>
            <h2>Un peu de contexte vaut mieux qu’un long cahier des charges.</h2>
            <ol>
                <li><span>01</span><p><b>Le point de départ</b>Ce qui existe aujourd’hui et ce qui ne fonctionne plus assez bien.</p></li>
                <li><span>02</span><p><b>Le résultat attendu</b>La décision, l’opération ou la capacité que vous voulez améliorer.</p></li>
                <li><span>03</span><p><b>Les contraintes</b>Les échéances, dépendances, données sensibles ou exigences déjà connues.</p></li>
            </ol>
            <div class="privacy-note"><i aria-hidden="true"></i><p>Les informations envoyées servent uniquement à traiter votre demande. N’indiquez aucun secret, mot de passe ou donnée personnelle non nécessaire.</p></div>
        </aside>
        <div class="form-panel" data-reveal>
            <div class="form-panel__header">
                <div><p class="section-kicker">Votre message</p><h2 id="contact-form-title">Parler à un expert</h2></div>
                <span>Champs marqués * obligatoires</span>
            </div>
            <?php require dirname(__DIR__) . '/components/form-errors.php'; ?>
            <form action="<?= e($contactPath) ?>" method="post" class="contact-form" data-form>
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="_timing" value="<?= e($timingToken) ?>">
                <input type="hidden" name="_idempotency" value="<?= e($idempotencyToken) ?>">
                <input type="hidden" name="locale" value="fr">
                <div class="form-trap" aria-hidden="true">
                    <label for="website">Votre site web</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-grid">
                    <div class="field field--full">
                        <label for="full_name">Nom complet <span aria-hidden="true">*</span></label>
                        <input id="full_name" name="full_name" type="text" value="<?= e($value('full_name')) ?>" autocomplete="name" maxlength="160" required<?= $hasError('full_name') ? ' aria-invalid="true" aria-describedby="full_name-error"' : '' ?>>
                        <?php if ($hasError('full_name')): ?><p class="field-error" id="full_name-error"><?= e($errorText('full_name')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="email">E-mail professionnel <span aria-hidden="true">*</span></label>
                        <input id="email" name="email" type="email" value="<?= e($value('email')) ?>" autocomplete="email" maxlength="254" inputmode="email" required<?= $hasError('email') ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                        <?php if ($hasError('email')): ?><p class="field-error" id="email-error"><?= e($errorText('email')) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="phone">Téléphone <span>(facultatif)</span></label>
                        <input id="phone" name="phone" type="tel" value="<?= e($value('phone')) ?>" autocomplete="tel" maxlength="40" inputmode="tel"<?= $hasError('phone') ? ' aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                        <?php if ($hasError('phone')): ?><p class="field-error" id="phone-error"><?= e($errorText('phone')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field--full">
                        <label for="organisation">Organisation <span>(facultatif)</span></label>
                        <input id="organisation" name="organisation" type="text" value="<?= e($value('organisation')) ?>" autocomplete="organization" maxlength="190"<?= $hasError('organisation') ? ' aria-invalid="true" aria-describedby="organisation-error"' : '' ?>>
                        <?php if ($hasError('organisation')): ?><p class="field-error" id="organisation-error"><?= e($errorText('organisation')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field--full">
                        <label for="subject">Sujet <span aria-hidden="true">*</span></label>
                        <select id="subject" name="subject" required<?= $hasError('subject') ? ' aria-invalid="true" aria-describedby="subject-error"' : '' ?>>
                            <option value="">Choisir un sujet</option>
                            <?php foreach ($subjects as $subjectValue => $label): ?><option value="<?= e($subjectValue) ?>"<?= $value('subject') === $subjectValue ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                        </select>
                        <?php if ($hasError('subject')): ?><p class="field-error" id="subject-error"><?= e($errorText('subject')) ?></p><?php endif; ?>
                    </div>
                    <div class="field field--full">
                        <label for="message">Votre contexte <span aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" rows="7" minlength="20" maxlength="5000" required aria-describedby="message-hint<?= $hasError('message') ? ' message-error' : '' ?>"><?= e($value('message')) ?></textarea>
                        <p class="field-hint" id="message-hint">Expliquez le point de départ, le résultat recherché et les contraintes déjà connues.</p>
                        <?php if ($hasError('message')): ?><p class="field-error" id="message-error"><?= e($errorText('message')) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="checkbox-field<?= $hasError('consent_privacy') ? ' checkbox-field--error' : '' ?>">
                    <input id="consent_privacy" name="consent_privacy" type="checkbox" value="1" required<?= !empty($old['consent_privacy']) ? ' checked' : '' ?><?= $hasError('consent_privacy') ? ' aria-invalid="true" aria-describedby="consent_privacy-error"' : '' ?>>
                    <label for="consent_privacy">J’accepte que SCTECH utilise ces informations pour traiter ma demande, selon la <a href="<?= e($privacyPath) ?>">politique de confidentialité</a>. <span aria-hidden="true">*</span></label>
                    <?php if ($hasError('consent_privacy')): ?><p class="field-error" id="consent_privacy-error"><?= e($errorText('consent_privacy')) ?></p><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="button button--ink" type="submit">Envoyer le message <span aria-hidden="true">↗</span></button>
                    <p>Votre demande est enregistrée avant notification. Aucun détail technique n’est affiché en cas d’incident d’envoi.</p>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="section section--sage contact-alternative" aria-labelledby="contact-alternative-title">
    <div class="shell editorial-split"><div data-reveal><p class="eyebrow">UN PROJET À STRUCTURER ?</p><h2 id="contact-alternative-title">Préparez un échange plus précis.</h2></div><div data-reveal><p>Le formulaire projet aide à décrire les domaines concernés, le contexte, l’objectif, les exigences de conformité et les intégrations.</p><a class="button button--outline" href="<?= e($projectPath) ?>">Étudier votre projet <span aria-hidden="true">→</span></a></div></div>
</section>

<?php

declare(strict_types=1);

use SCTech\Support\ProjectInquiryCatalog;

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$csrfToken = (string) ($csrfToken ?? '');
$timingToken = (string) ($timingToken ?? ($formTimingToken ?? ''));
$idempotencyToken = (string) ($idempotencyToken ?? '');
$value = static fn (string $key): string => isset($old[$key]) && is_scalar($old[$key])
    ? (string) $old[$key]
    : '';
$hasError = static fn (string $key): bool => isset($errors[$key]);
$errorText = static function (string $key) use ($errors): string {
    $error = $errors[$key] ?? '';

    return is_array($error) ? (string) reset($error) : (string) $error;
};
$selectedServices = $old['services_researched'] ?? [];
if (is_string($selectedServices)) {
    $selectedServices = array_filter(array_map('trim', explode(',', $selectedServices)));
}
$selectedServices = is_array($selectedServices) ? $selectedServices : [];
$projectPath = '/demander-un-projet';
$formAction = '/demander-un-projet';
if (isset($routeUrl) && is_callable($routeUrl)) {
    $projectPath = (string) $routeUrl('project');
    $formAction = (string) $routeUrl('project.submit');
}
$breadcrumbs = [['label' => 'Étudier votre projet', 'href' => $projectPath]];
require dirname(__DIR__) . '/components/breadcrumbs.php';
?>
<section class="quote-hero">
    <div class="shell quote-hero__grid">
        <div data-reveal>
            <p class="section-kicker">Étudier votre projet</p>
            <h1>Décrivons le système financier à construire.</h1>
            <p>Partagez le contexte, les règles et les systèmes concernés. SCTECH utilisera ces éléments pour préparer un premier échange de cadrage, sans transformer vos indications en engagement automatique.</p>
        </div>
        <ol aria-label="Étapes de la demande" data-reveal data-reveal-delay="1">
            <li><span>01</span>Votre organisation</li>
            <li><span>02</span>Le produit et ses règles</li>
            <li><span>03</span>Les contraintes de réalisation</li>
        </ol>
    </div>
</section>

<section class="section quote-section" aria-labelledby="quote-form-title">
    <div class="shell quote-layout">
        <aside class="quote-layout__aside" data-reveal>
            <p class="section-kicker">Avant de commencer</p>
            <h2>Un premier cadrage fondé sur des informations utiles.</h2>
            <p>Vous pouvez laisser les exigences de conformité et les intégrations à préciser. Les champs obligatoires servent à qualifier le problème, l’objectif et l’horizon.</p>
            <dl>
                <div><dt>Confidentialité</dt><dd>N’envoyez aucun secret, mot de passe, document d’identité ou donnée financière personnelle.</dd></div>
                <div><dt>Finance islamique</dt><dd>Les critères religieux sont définis ou validés par les instances compétentes de votre organisation.</dd></div>
                <div><dt>Prochain pas</dt><dd>La demande prépare un échange ; elle ne produit ni devis automatique ni conseil en investissement.</dd></div>
            </dl>
        </aside>

        <div class="form-panel form-panel--quote" data-reveal>
            <div class="form-panel__header">
                <div><p class="section-kicker">Votre projet</p><h2 id="quote-form-title">Préparer la discussion</h2></div>
                <span>Champs marqués * obligatoires</span>
            </div>
            <?php require dirname(__DIR__) . '/components/form-errors.php'; ?>
            <form action="<?= e($formAction) ?>" method="post" data-form>
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="_timing" value="<?= e($timingToken) ?>">
                <input type="hidden" name="_idempotency" value="<?= e($idempotencyToken) ?>">
                <input type="hidden" name="locale" value="fr">
                <div class="form-trap" aria-hidden="true">
                    <label for="website">Votre site web</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <fieldset class="form-step">
                    <legend><span>01</span> Vous & votre organisation</legend>
                    <div class="form-grid">
                        <div class="field">
                            <label for="full_name">Nom complet <span aria-hidden="true">*</span></label>
                            <input id="full_name" name="full_name" type="text" value="<?= e($value('full_name')) ?>" autocomplete="name" maxlength="160" required<?= $hasError('full_name') ? ' aria-invalid="true" aria-describedby="full_name-error"' : '' ?>>
                            <?php if ($hasError('full_name')): ?><p class="field-error" id="full_name-error"><?= e($errorText('full_name')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="organisation">Entreprise <span aria-hidden="true">*</span></label>
                            <input id="organisation" name="organisation" type="text" value="<?= e($value('organisation')) ?>" autocomplete="organization" maxlength="190" required<?= $hasError('organisation') ? ' aria-invalid="true" aria-describedby="organisation-error"' : '' ?>>
                            <?php if ($hasError('organisation')): ?><p class="field-error" id="organisation-error"><?= e($errorText('organisation')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="email">E-mail professionnel <span aria-hidden="true">*</span></label>
                            <input id="email" name="email" type="email" value="<?= e($value('email')) ?>" autocomplete="email" maxlength="254" required<?= $hasError('email') ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                            <?php if ($hasError('email')): ?><p class="field-error" id="email-error"><?= e($errorText('email')) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="phone">Téléphone <span class="field-optional">facultatif</span></label>
                            <input id="phone" name="phone" type="tel" value="<?= e($value('phone')) ?>" autocomplete="tel" maxlength="40" inputmode="tel"<?= $hasError('phone') ? ' aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                            <?php if ($hasError('phone')): ?><p class="field-error" id="phone-error"><?= e($errorText('phone')) ?></p><?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-step">
                    <legend><span>02</span> Le produit & sa logique</legend>
                    <div class="form-grid">
                        <div class="field field--full">
                            <label for="project_type">Type de projet <span aria-hidden="true">*</span></label>
                            <select id="project_type" name="project_type" required<?= $hasError('project_type') ? ' aria-invalid="true" aria-describedby="project_type-error"' : '' ?>>
                                <option value="">Choisir un type de projet</option>
                                <?php foreach (ProjectInquiryCatalog::projectTypes() as $optionValue => $label): ?>
                                    <option value="<?= e($optionValue) ?>"<?= $value('project_type') === $optionValue ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($hasError('project_type')): ?><p class="field-error" id="project_type-error"><?= e($errorText('project_type')) ?></p><?php endif; ?>
                        </div>

                        <fieldset class="choice-group field--full" id="services_researched" aria-describedby="services-researched-hint<?= $hasError('services_researched') ? ' services_researched-error' : '' ?>"<?= $hasError('services_researched') ? ' aria-invalid="true"' : '' ?>>
                            <legend>Domaines concernés <span aria-hidden="true">*</span></legend>
                            <p class="field-hint" id="services-researched-hint">Choisissez entre un et trois domaines. La première sélection sert uniquement de clé de classement interne.</p>
                            <div class="choice-grid">
                                <?php foreach (ProjectInquiryCatalog::solutionDomains() as $optionValue => $label): ?>
                                    <div class="choice-card">
                                        <input id="service_<?= e($optionValue) ?>" name="services_researched[]" type="checkbox" value="<?= e($optionValue) ?>"<?= in_array($optionValue, $selectedServices, true) ? ' checked' : '' ?>>
                                        <label for="service_<?= e($optionValue) ?>"><?= e($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($hasError('services_researched')): ?><p class="field-error" id="services_researched-error"><?= e($errorText('services_researched')) ?></p><?php endif; ?>
                        </fieldset>

                        <div class="field field--full">
                            <label for="project_context">Contexte <span aria-hidden="true">*</span></label>
                            <textarea id="project_context" name="project_context" rows="7" minlength="20" maxlength="5000" required aria-describedby="project-context-hint<?= $hasError('project_context') ? ' project_context-error' : '' ?>"<?= $hasError('project_context') ? ' aria-invalid="true"' : '' ?>><?= e($value('project_context')) ?></textarea>
                            <p class="field-hint" id="project-context-hint">Décrivez le produit, les parties prenantes, l’existant et la contrainte qui motive le projet.</p>
                            <?php if ($hasError('project_context')): ?><p class="field-error" id="project_context-error"><?= e($errorText('project_context')) ?></p><?php endif; ?>
                        </div>
                        <div class="field field--full">
                            <label for="project_objective">Objectif <span aria-hidden="true">*</span></label>
                            <textarea id="project_objective" name="project_objective" rows="5" minlength="10" maxlength="3000" required aria-describedby="project-objective-hint<?= $hasError('project_objective') ? ' project_objective-error' : '' ?>"<?= $hasError('project_objective') ? ' aria-invalid="true"' : '' ?>><?= e($value('project_objective')) ?></textarea>
                            <p class="field-hint" id="project-objective-hint">Indiquez ce que le produit doit permettre de décider, automatiser ou rendre traçable.</p>
                            <?php if ($hasError('project_objective')): ?><p class="field-error" id="project_objective-error"><?= e($errorText('project_objective')) ?></p><?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-step">
                    <legend><span>03</span> Conformité, intégrations & horizon</legend>
                    <div class="form-grid">
                        <div class="field field--full">
                            <label for="compliance_requirements">Exigences de conformité <span class="field-optional">facultatif</span></label>
                            <textarea id="compliance_requirements" name="compliance_requirements" rows="5" maxlength="4000" aria-describedby="compliance-requirements-hint<?= $hasError('compliance_requirements') ? ' compliance_requirements-error' : '' ?>"<?= $hasError('compliance_requirements') ? ' aria-invalid="true"' : '' ?>><?= e($value('compliance_requirements')) ?></textarea>
                            <p class="field-hint" id="compliance-requirements-hint">Précisez les référentiels ou validations attendus. Les critères religieux et de conformité restent définis ou validés par les instances compétentes de votre organisation ; SCTECH les traduit en règles et contrôles logiciels.</p>
                            <?php if ($hasError('compliance_requirements')): ?><p class="field-error" id="compliance_requirements-error"><?= e($errorText('compliance_requirements')) ?></p><?php endif; ?>
                        </div>
                        <div class="field field--full">
                            <label for="integration_requirements">Systèmes à intégrer <span class="field-optional">facultatif</span></label>
                            <textarea id="integration_requirements" name="integration_requirements" rows="5" maxlength="4000" aria-describedby="integration-requirements-hint<?= $hasError('integration_requirements') ? ' integration_requirements-error' : '' ?>"<?= $hasError('integration_requirements') ? ' aria-invalid="true"' : '' ?>><?= e($value('integration_requirements')) ?></textarea>
                            <p class="field-hint" id="integration-requirements-hint">API, core system, CRM, KYC, paiement, référentiels, fichiers ou outils de reporting.</p>
                            <?php if ($hasError('integration_requirements')): ?><p class="field-error" id="integration_requirements-error"><?= e($errorText('integration_requirements')) ?></p><?php endif; ?>
                        </div>
                        <div class="field field--full">
                            <label for="timeline">Échéance <span aria-hidden="true">*</span></label>
                            <select id="timeline" name="timeline" required<?= $hasError('timeline') ? ' aria-invalid="true" aria-describedby="timeline-error"' : '' ?>>
                                <option value="">Choisir une échéance</option>
                                <?php foreach (ProjectInquiryCatalog::timelines() as $optionValue => $label): ?>
                                    <option value="<?= e($optionValue) ?>"<?= $value('timeline') === $optionValue ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($hasError('timeline')): ?><p class="field-error" id="timeline-error"><?= e($errorText('timeline')) ?></p><?php endif; ?>
                        </div>
                        <div class="field field--full">
                            <label for="project_summary">Message complémentaire <span class="field-optional">facultatif</span></label>
                            <textarea id="project_summary" name="project_summary" rows="5" maxlength="4000"<?= $hasError('project_summary') ? ' aria-invalid="true" aria-describedby="project_summary-error"' : '' ?>><?= e($value('project_summary')) ?></textarea>
                            <?php if ($hasError('project_summary')): ?><p class="field-error" id="project_summary-error"><?= e($errorText('project_summary')) ?></p><?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <div class="checkbox-field<?= $hasError('consent_privacy') ? ' checkbox-field--error' : '' ?>">
                    <input id="consent_privacy" name="consent_privacy" type="checkbox" value="1" required<?= !empty($old['consent_privacy']) ? ' checked' : '' ?><?= $hasError('consent_privacy') ? ' aria-invalid="true" aria-describedby="consent_privacy-error"' : '' ?>>
                    <label for="consent_privacy">J’accepte que SCTECH utilise ces informations pour traiter ma demande, selon la <a href="/politique-confidentialite">politique de confidentialité</a>. <span aria-hidden="true">*</span></label>
                    <?php if ($hasError('consent_privacy')): ?><p class="field-error" id="consent_privacy-error"><?= e($errorText('consent_privacy')) ?></p><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="button button--ink" type="submit">Étudier mon projet <span aria-hidden="true">↗</span></button>
                    <p>La demande est enregistrée avant notification et le même envoi peut être repris sans créer de doublon.</p>
                </div>
            </form>
        </div>
    </div>
</section>

<?php

declare(strict_types=1);

$record = is_array($record ?? null) ? $record : [];
$errors = is_array($errors ?? null) ? $errors : [];
$isEdit = !empty($record['id']);
$isCaseStudy = ($type ?? '') === 'realisations';
$isSolution = ($type ?? '') === 'expertises';
$isArticle = ($type ?? '') === 'articles';
$isAdmin = is_array($user ?? null) && ($user['role'] ?? '') === 'admin';
$statusOptions = ['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'];
if ($isCaseStudy && !$isAdmin) {
    unset($statusOptions['published']);
}
$fieldError = static fn (string $field): string => isset($errors[$field]) ? (string) $errors[$field][0] : '';
$categoryOptions = [
    'finance-islamique' => 'Finance islamique & technologie',
    'experience-investisseur' => 'Expérience investisseur',
    'conformite' => 'Workflows de conformité',
    'architecture-financiere' => 'Architecture financière',
    'data-reporting' => 'Data & reporting',
    'securite' => 'Sécurité',
];
?>
<header class="admin-page-header">
    <div>
        <p class="eyebrow"><?= e((string) $typeLabel) ?></p>
        <h1><?= $isEdit ? 'Modifier' : 'Créer' ?> un contenu</h1>
    </div>
    <a href="/admin/<?= e((string) $type) ?>">Retour à la liste</a>
</header>

<?php if ($errors !== []): ?>
    <div class="form-error-summary" role="alert" tabindex="-1">
        <h2>Vérifiez les champs signalés</h2>
        <ul>
            <?php foreach ($errors as $field => $messages): ?>
                <?php foreach ((array) $messages as $message): ?>
                    <li><a href="#<?= e($field === '_form' ? 'formulaire-contenu' : $field) ?>"><?= e((string) $message) ?></a></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form id="formulaire-contenu" method="post" action="/admin/<?= e((string) $type) ?><?= $isEdit ? '/' . e((string) $record['id']) : '' ?>" novalidate>
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="version" value="<?= e((string) $record['version']) ?>">
    <?php endif; ?>
    <input type="hidden" name="locale" value="<?= e((string) ($record['locale'] ?? 'fr')) ?>">

    <fieldset>
        <legend>Identité éditoriale</legend>
        <div class="field">
            <label for="content_key">Clé stable</label>
            <input id="content_key" name="content_key" required maxlength="120" pattern="[a-z0-9][a-z0-9._-]{2,119}" value="<?= e((string) ($record['content_key'] ?? '')) ?>" aria-describedby="content-key-aide<?= $fieldError('content_key') !== '' ? ' content_key-erreur' : '' ?>"<?= $fieldError('content_key') !== '' ? ' aria-invalid="true"' : '' ?>>
            <p id="content-key-aide" class="field-hint">Ne la changez pas après publication. Exemple : <code>page.contact</code>.</p>
            <?php if ($fieldError('content_key') !== ''): ?><p id="content_key-erreur" class="field-error"><?= e($fieldError('content_key')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" required maxlength="190" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" value="<?= e((string) ($record['slug'] ?? '')) ?>"<?= $fieldError('slug') !== '' ? ' aria-invalid="true" aria-describedby="slug-erreur"' : '' ?>>
            <?php if ($fieldError('slug') !== ''): ?><p id="slug-erreur" class="field-error"><?= e($fieldError('slug')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="title">Titre</label>
            <input id="title" name="title" required maxlength="190" value="<?= e((string) ($record['title'] ?? '')) ?>"<?= $fieldError('title') !== '' ? ' aria-invalid="true" aria-describedby="title-erreur"' : '' ?>>
            <?php if ($fieldError('title') !== ''): ?><p id="title-erreur" class="field-error"><?= e($fieldError('title')) ?></p><?php endif; ?>
        </div>
        <?php if (!$isCaseStudy): ?>
            <div class="field">
                <label for="eyebrow">Surtitre</label>
                <input id="eyebrow" name="eyebrow" maxlength="120" value="<?= e((string) ($record['eyebrow'] ?? '')) ?>"<?= $fieldError('eyebrow') !== '' ? ' aria-invalid="true" aria-describedby="eyebrow-erreur"' : '' ?>>
                <?php if ($fieldError('eyebrow') !== ''): ?><p id="eyebrow-erreur" class="field-error"><?= e($fieldError('eyebrow')) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" rows="5" maxlength="5000"<?= $type === 'articles' ? ' required' : '' ?><?= $fieldError('summary') !== '' ? ' aria-invalid="true" aria-describedby="summary-erreur"' : '' ?>><?= e((string) ($record['summary'] ?? '')) ?></textarea>
            <?php if ($fieldError('summary') !== ''): ?><p id="summary-erreur" class="field-error"><?= e($fieldError('summary')) ?></p><?php endif; ?>
        </div>
        <?php if ($isSolution): ?>
            <div class="field">
                <label for="problem_text">Contexte métier</label>
                <textarea id="problem_text" name="problem_text" rows="5" maxlength="5000"<?= $fieldError('problem_text') !== '' ? ' aria-invalid="true" aria-describedby="problem_text-erreur"' : '' ?>><?= e((string) ($record['problem_text'] ?? '')) ?></textarea>
                <?php if ($fieldError('problem_text') !== ''): ?><p id="problem_text-erreur" class="field-error"><?= e($fieldError('problem_text')) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="positioning_text">Positionnement de la solution</label>
                <textarea id="positioning_text" name="positioning_text" rows="5" maxlength="5000"<?= $fieldError('positioning_text') !== '' ? ' aria-invalid="true" aria-describedby="positioning_text-erreur"' : '' ?>><?= e((string) ($record['positioning_text'] ?? '')) ?></textarea>
                <?php if ($fieldError('positioning_text') !== ''): ?><p id="positioning_text-erreur" class="field-error"><?= e($fieldError('positioning_text')) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($isArticle): ?>
            <div class="field">
                <label for="category_key">Catégorie</label>
                <select id="category_key" name="category_key" required<?= $fieldError('category_key') !== '' ? ' aria-invalid="true" aria-describedby="category_key-erreur"' : '' ?>>
                    <option value="">Choisir une catégorie</option>
                    <?php foreach ($categoryOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= ($record['category_key'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                </select>
                <?php if ($fieldError('category_key') !== ''): ?><p id="category_key-erreur" class="field-error"><?= e($fieldError('category_key')) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="reading_minutes">Temps de lecture (minutes)</label>
                <input id="reading_minutes" name="reading_minutes" type="number" min="1" max="60" value="<?= e((string) ($record['reading_minutes'] ?? 4)) ?>" required aria-describedby="reading-minutes-aide<?= $fieldError('reading_minutes') !== '' ? ' reading_minutes-erreur' : '' ?>"<?= $fieldError('reading_minutes') !== '' ? ' aria-invalid="true"' : '' ?>>
                <p id="reading-minutes-aide" class="field-hint">Nombre entier compris entre 1 et 60.</p>
                <?php if ($fieldError('reading_minutes') !== ''): ?><p id="reading_minutes-erreur" class="field-error"><?= e($fieldError('reading_minutes')) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
    </fieldset>

    <fieldset>
        <legend>Contenu structuré</legend>
        <div class="field">
            <label for="blocks_json">Blocs JSON version 1</label>
            <textarea id="blocks_json" name="blocks_json" rows="18" spellcheck="false" aria-describedby="blocks-aide<?= $fieldError('blocks_json') !== '' ? ' blocks_json-erreur' : '' ?>"<?= $fieldError('blocks_json') !== '' ? ' aria-invalid="true"' : '' ?>><?= e((string) ($record['blocks_json'] ?? '{"version":1,"blocks":[]}')) ?></textarea>
            <p id="blocks-aide" class="field-hint">Types autorisés : paragraph, heading (niveaux 2–3), list, quote et callout. Aucun HTML n’est accepté.</p>
            <?php if ($fieldError('blocks_json') !== ''): ?><p id="blocks_json-erreur" class="field-error"><?= e($fieldError('blocks_json')) ?></p><?php endif; ?>
        </div>
    </fieldset>

    <fieldset>
        <legend>Publication et référencement</legend>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status"<?= $fieldError('status') !== '' ? ' aria-invalid="true" aria-describedby="status-erreur"' : '' ?>>
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($record['status'] ?? 'draft') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($isCaseStudy && !$isAdmin): ?><p id="verify_for_publication" class="field-hint">Un éditeur peut préparer ou archiver une réalisation. Sa publication est réservée aux administrateurs.</p><?php endif; ?>
            <?php if ($fieldError('status') !== ''): ?><p id="status-erreur" class="field-error"><?= e($fieldError('status')) ?></p><?php endif; ?>
        </div>
        <?php if ($isCaseStudy): ?>
            <div class="field">
                <p><strong>Validation des affirmations</strong></p>
                <?php if (!empty($record['verified_at']) && !empty($record['verified_by'])): ?>
                    <p class="field-hint">Validation enregistrée le <?= e((string) $record['verified_at']) ?> par l’administrateur #<?= e((string) $record['verified_by']) ?>. Une modification du titre, du résumé ou des blocs révoque cette validation.</p>
                <?php else: ?>
                    <p class="field-hint">Aucune validation active. La réalisation reste invisible publiquement, même si son statut est « Publié ».</p>
                <?php endif; ?>
            </div>
            <?php if ($isAdmin): ?>
                <div class="field">
                    <label for="verification_notes">Preuve et périmètre de validation</label>
                    <textarea id="verification_notes" name="verification_notes" rows="5" maxlength="2000"<?= $fieldError('verification_notes') !== '' ? ' aria-invalid="true" aria-describedby="verification-notes-aide verification_notes-erreur"' : ' aria-describedby="verification-notes-aide"' ?>><?= e((string) ($record['verification_notes'] ?? '')) ?></textarea>
                    <p id="verification-notes-aide" class="field-hint">Indiquez la source, la date et les affirmations autorisées. N’insérez ni secret ni donnée personnelle inutile.</p>
                    <?php if ($fieldError('verification_notes') !== ''): ?><p id="verification_notes-erreur" class="field-error"><?= e($fieldError('verification_notes')) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="verify_for_publication">
                        <input id="verify_for_publication" name="verify_for_publication" type="checkbox" value="1"<?= !empty($record['verify_for_publication']) ? ' checked' : '' ?><?= $fieldError('verify_for_publication') !== '' ? ' aria-invalid="true" aria-describedby="verify_for_publication-erreur"' : '' ?>>
                        J’atteste que chaque affirmation de cette version est vérifiée et autorisée à la publication.
                    </label>
                    <?php if ($fieldError('verify_for_publication') !== ''): ?><p id="verify_for_publication-erreur" class="field-error"><?= e($fieldError('verify_for_publication')) ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="field"><label for="sort_order">Ordre</label><input id="sort_order" name="sort_order" type="number" step="1" value="<?= e((string) ($record['sort_order'] ?? 0)) ?>"></div>
        <div class="field"><label for="seo_title">Titre SEO</label><input id="seo_title" name="seo_title" maxlength="190" value="<?= e((string) ($record['seo_title'] ?? '')) ?>"></div>
        <div class="field"><label for="seo_description">Description SEO</label><textarea id="seo_description" name="seo_description" rows="3" maxlength="320"><?= e((string) ($record['seo_description'] ?? '')) ?></textarea></div>
    </fieldset>
    <div class="form-actions">
        <button type="submit" class="button button--primary">Enregistrer</button>
        <a class="button button--quiet" href="/admin/<?= e((string) $type) ?>">Annuler</a>
    </div>
</form>

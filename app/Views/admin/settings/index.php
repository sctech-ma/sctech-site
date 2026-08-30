<?php

declare(strict_types=1);

$settings = is_array($settings ?? null) ? $settings : [];
$errors = is_array($errors ?? null) ? $errors : [];
?>
<header class="admin-page-header">
    <div><p class="eyebrow">Accès administrateur</p><h1>Paramètres du site</h1><p>Les informations légales incomplètes maintiennent le lancement bloqué.</p></div>
</header>

<?php if ($errors !== []): ?>
    <div class="form-error-summary" role="alert"><h2>Le paramètre n’a pas été enregistré</h2><ul><?php foreach ($errors as $messages): ?><?php foreach ((array) $messages as $message): ?><li><?= e((string) $message) ?></li><?php endforeach; ?><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-settings-list">
    <?php foreach ($settings as $setting): ?>
        <form method="post" action="/admin/parametres" class="admin-setting">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <input type="hidden" name="version" value="<?= e((string) $setting['version']) ?>">
            <input type="hidden" name="locale" value="<?= e((string) $setting['locale']) ?>">
            <div class="field"><label for="key-<?= e((string) $setting['id']) ?>">Clé</label><input id="key-<?= e((string) $setting['id']) ?>" name="content_key" readonly value="<?= e((string) $setting['content_key']) ?>"></div>
            <div class="field"><label for="value-<?= e((string) $setting['id']) ?>">Valeur JSON</label><textarea id="value-<?= e((string) $setting['id']) ?>" name="value_json" rows="7" spellcheck="false"><?= e((string) $setting['value_json']) ?></textarea></div>
            <div class="field"><label for="status-<?= e((string) $setting['id']) ?>">Statut</label><select id="status-<?= e((string) $setting['id']) ?>" name="status"><?php foreach (['draft', 'published', 'archived'] as $status): ?><option value="<?= e($status) ?>"<?= $setting['status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
            <label><input type="checkbox" name="is_sensitive" value="1"<?= (int) $setting['is_sensitive'] === 1 ? ' checked' : '' ?>> Valeur sensible</label>
            <button type="submit" class="button button--secondary">Enregistrer ce paramètre</button>
        </form>
    <?php endforeach; ?>
</div>

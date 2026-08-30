<?php

declare(strict_types=1);

$media = is_array($media ?? null) ? $media : [];
$errors = is_array($errors ?? null) ? $errors : [];
?>
<header class="admin-page-header"><div><p class="eyebrow">Bibliothèque</p><h1>Médias</h1><p>JPEG, PNG ou WebP uniquement, jusqu’à 5 Mo et 20 mégapixels.</p></div></header>

<?php if ($errors !== []): ?>
    <div class="form-error-summary" role="alert"><h2>Le média n’a pas été téléversé</h2><ul><?php foreach ($errors as $messages): ?><?php foreach ((array) $messages as $message): ?><li><?= e((string) $message) ?></li><?php endforeach; ?><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="/admin/medias" enctype="multipart/form-data" class="admin-upload-form">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <div class="field"><label for="media">Fichier image</label><input id="media" name="media" type="file" accept="image/jpeg,image/png,image/webp" required></div>
    <div class="field"><label for="alt_text">Alternative textuelle</label><input id="alt_text" name="alt_text" required minlength="2" maxlength="255"><p class="field-hint">Décrivez l’information portée par l’image, sans écrire « image de ».</p></div>
    <button type="submit" class="button button--primary">Téléverser</button>
</form>

<?php if ($media === []): ?>
    <p class="empty-state">Aucun média n’a encore été ajouté.</p>
<?php else: ?>
    <ul class="admin-media-grid">
        <?php foreach ($media as $item): ?>
            <li>
                <img src="/<?= e((string) $item['path']) ?>" alt="<?= e((string) $item['alt_text']) ?>" width="<?= e((string) ($item['width'] ?? 320)) ?>" height="<?= e((string) ($item['height'] ?? 180)) ?>" loading="lazy">
                <p><strong><?= e((string) $item['alt_text']) ?></strong></p>
                <p><?= e((string) $item['mime_type']) ?> · <?= e((string) $item['byte_size']) ?> octets · <?= e((string) $item['status']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

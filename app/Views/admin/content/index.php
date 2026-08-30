<?php

declare(strict_types=1);

$records = is_array($records ?? null) ? $records : [];
?>
<header class="admin-page-header">
    <div>
        <p class="eyebrow">Contenus</p>
        <h1><?= e((string) $typeLabel) ?></h1>
    </div>
    <a class="button button--primary" href="/admin/<?= e((string) $type) ?>/nouveau">Créer</a>
</header>

<form method="get" class="admin-filters" aria-label="Filtrer les contenus">
    <label for="statut">Statut</label>
    <select id="statut" name="statut">
        <option value="">Tous</option>
        <?php foreach (['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $statusFilter === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="button button--secondary">Filtrer</button>
</form>

<?php if ($records === []): ?>
    <p class="empty-state">Aucun contenu ne correspond à ce filtre.</p>
<?php else: ?>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Liste des contenus">
        <table>
            <thead><tr><th scope="col">Titre</th><th scope="col">Slug</th><th scope="col">Statut</th><th scope="col">Mise à jour</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <th scope="row"><?= e((string) $record['title']) ?></th>
                        <td><code><?= e((string) $record['slug']) ?></code></td>
                        <td><span class="status status--<?= e((string) $record['status']) ?>"><?= e((string) $record['status']) ?></span></td>
                        <td><?= e((string) $record['updated_at']) ?></td>
                        <td class="table-actions">
                            <a href="/admin/<?= e((string) $type) ?>/<?= e((string) $record['id']) ?>/modifier">Modifier<span class="sr-only"> <?= e((string) $record['title']) ?></span></a>
                            <a href="/admin/preview/<?= e((string) $type) ?>/<?= e((string) $record['id']) ?>" target="_blank" rel="noopener">Prévisualiser<span class="sr-only"> <?= e((string) $record['title']) ?> (nouvel onglet)</span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

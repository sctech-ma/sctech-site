<?php

declare(strict_types=1);

use SCTech\Support\ProjectInquiryCatalog;

$records = is_array($records ?? null) ? $records : [];
$isQuote = $type === 'devis';
?>
<header class="admin-page-header">
    <div>
        <p class="eyebrow">Données personnelles — accès administrateur</p>
        <h1><?= $isQuote ? 'Demandes de projet' : 'Messages' ?></h1>
    </div>
</header>

<form method="get" class="admin-filters" aria-label="Filtrer les demandes">
    <label for="statut">Statut</label>
    <select id="statut" name="statut">
        <option value="">Tous</option>
        <?php $statuses = $isQuote ? ['new', 'qualified', 'proposal', 'closed', 'spam'] : ['new', 'in_progress', 'closed', 'spam']; ?>
        <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"<?= $statusFilter === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="button button--secondary">Filtrer</button>
</form>

<?php if ($records === []): ?>
    <p class="empty-state">Aucune demande ne correspond à ce filtre.</p>
<?php else: ?>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes reçues">
        <table>
            <thead><tr><th scope="col">Référence</th><th scope="col">Contact</th><th scope="col"><?= $isQuote ? 'Solution' : 'Sujet' ?></th><th scope="col">Statut</th><th scope="col">Reçu le</th><th scope="col"><span class="sr-only">Action</span></th></tr></thead>
            <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <th scope="row"><code><?= e((string) $record['public_id']) ?></code></th>
                    <td><?= e((string) $record['full_name']) ?><br><span><?= e((string) $record['organisation']) ?></span></td>
                    <td><?= e($isQuote
                        ? ProjectInquiryCatalog::label(
                            ProjectInquiryCatalog::solutionDomains(),
                            (string) $record['service_key'],
                        )
                        : (string) $record['subject']) ?></td>
                    <td><span class="status"><?= e((string) $record['workflow_status']) ?></span></td>
                    <td><?= e((string) $record['created_at']) ?></td>
                    <td><a href="/admin/<?= e((string) $type) ?>/<?= e((string) $record['id']) ?>">Ouvrir<span class="sr-only"> la demande <?= e((string) $record['public_id']) ?></span></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

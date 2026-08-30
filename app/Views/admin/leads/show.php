<?php

declare(strict_types=1);

use SCTech\Support\ProjectInquiryCatalog;

$record = is_array($record ?? null) ? $record : [];
$isQuote = $type === 'devis';
$services = [];
if ($isQuote && isset($record['services_researched_json'])) {
    $decoded = json_decode((string) $record['services_researched_json'], true);
    $services = is_array($decoded) ? array_filter($decoded, 'is_string') : [];
}
if ($isQuote && $services === [] && !empty($record['service_key'])) {
    $services = [(string) $record['service_key']];
}
?>
<header class="admin-page-header">
    <div>
        <p class="eyebrow"><?= $isQuote ? 'Demande de projet' : 'Message' ?></p>
        <h1><?= e((string) $record['public_id']) ?></h1>
        <p>Reçu le <?= e((string) $record['created_at']) ?> · notification <?= e((string) $record['notification_status']) ?></p>
    </div>
    <a href="/admin/<?= e((string) $type) ?>">Retour à la liste</a>
</header>

<section aria-labelledby="contact-demande">
    <h2 id="contact-demande">Contact</h2>
    <dl class="admin-details">
        <div><dt>Nom</dt><dd><?= e((string) $record['full_name']) ?></dd></div>
        <div><dt>Organisation</dt><dd><?= e((string) ($record['organisation'] ?? '—')) ?></dd></div>
        <div><dt>E-mail</dt><dd><a href="mailto:<?= e((string) $record['email']) ?>"><?= e((string) $record['email']) ?></a></dd></div>
        <div><dt>Téléphone</dt><dd><?= e(!empty($record['phone']) ? (string) $record['phone'] : '—') ?></dd></div>
        <?php if ($isQuote && !empty($record['preferred_contact_method'])): ?><div><dt>Contact préféré</dt><dd><?= e(ProjectInquiryCatalog::label(ProjectInquiryCatalog::contactMethods(), (string) $record['preferred_contact_method'])) ?></dd></div><?php endif; ?>
    </dl>
</section>

<section aria-labelledby="besoin-demande">
    <h2 id="besoin-demande">Besoin exprimé</h2>
    <?php if ($isQuote): ?>
        <dl class="admin-details">
            <div><dt>Type de projet</dt><dd><?= e(ProjectInquiryCatalog::label(ProjectInquiryCatalog::projectTypes(), (string) $record['project_type'])) ?></dd></div>
            <div><dt>Domaines concernés</dt><dd><?= e(implode(', ', array_map(static fn (string $key): string => ProjectInquiryCatalog::label(ProjectInquiryCatalog::solutionDomains(), $key), $services))) ?></dd></div>
            <?php if (!empty($record['budget_range'])): ?><div><dt>Budget</dt><dd><?= e(ProjectInquiryCatalog::label(ProjectInquiryCatalog::budgets(), (string) $record['budget_range'])) ?></dd></div><?php endif; ?>
            <div><dt>Horizon</dt><dd><?= e(ProjectInquiryCatalog::label(ProjectInquiryCatalog::timelines(), (string) ($record['timeline'] ?? 'a-definir'))) ?></dd></div>
        </dl>
        <h3>Contexte</h3><p class="preserve-lines"><?= e((string) ($record['project_context'] ?? '')) ?></p>
        <h3>Objectif</h3><p class="preserve-lines"><?= e((string) ($record['project_objective'] ?? '')) ?></p>
        <?php if (!empty($record['compliance_requirements'])): ?><h3>Exigences de conformité</h3><p class="preserve-lines"><?= e((string) $record['compliance_requirements']) ?></p><?php endif; ?>
        <?php if (!empty($record['integration_requirements'])): ?><h3>Systèmes à intégrer</h3><p class="preserve-lines"><?= e((string) $record['integration_requirements']) ?></p><?php endif; ?>
        <?php if (!empty($record['project_summary'])): ?><h3>Message complémentaire</h3><p class="preserve-lines"><?= e((string) $record['project_summary']) ?></p><?php endif; ?>
    <?php else: ?>
        <p><strong>Sujet :</strong> <?= e((string) $record['subject']) ?></p>
        <p class="preserve-lines"><?= e((string) $record['message']) ?></p>
    <?php endif; ?>
</section>

<form method="post" action="/admin/<?= e((string) $type) ?>/<?= e((string) $record['id']) ?>" class="admin-status-form">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <input type="hidden" name="version" value="<?= e((string) $record['version']) ?>">
    <label for="workflow_status">Statut de suivi</label>
    <select id="workflow_status" name="workflow_status">
        <?php $statuses = $isQuote ? ['new', 'qualified', 'proposal', 'closed', 'spam'] : ['new', 'in_progress', 'closed', 'spam']; ?>
        <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"<?= $record['workflow_status'] === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="button button--primary">Mettre à jour</button>
</form>

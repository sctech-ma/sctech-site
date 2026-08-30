<?php

declare(strict_types=1);

$record = is_array($record ?? null) ? $record : [];
$blocks = is_array($blocks ?? null) ? $blocks : [];
?>
<article class="admin-preview">
    <header class="admin-page-header">
        <div>
            <p class="eyebrow">Prévisualisation authentifiée — <?= e((string) ($record['status'] ?? 'draft')) ?></p>
            <h1><?= e((string) ($record['title'] ?? 'Sans titre')) ?></h1>
            <?php if (!empty($record['summary'])): ?><p class="lede"><?= e((string) $record['summary']) ?></p><?php endif; ?>
        </div>
        <p class="notice">Cette URL est privée et exclue de l’indexation.</p>
    </header>

    <div class="prose">
        <?php foreach ($blocks as $block): ?>
            <?php if (!is_array($block) || !is_string($block['type'] ?? null)) { continue; } ?>
            <?php if ($block['type'] === 'paragraph'): ?>
                <p><?= e((string) ($block['text'] ?? '')) ?></p>
            <?php elseif ($block['type'] === 'heading'): ?>
                <?php if (($block['level'] ?? 2) === 3): ?><h3><?= e((string) ($block['text'] ?? '')) ?></h3><?php else: ?><h2><?= e((string) ($block['text'] ?? '')) ?></h2><?php endif; ?>
            <?php elseif ($block['type'] === 'list'): ?>
                <?php if (!empty($block['title'])): ?><h2><?= e((string) $block['title']) ?></h2><?php endif; ?>
                <ul><?php foreach ((array) ($block['items'] ?? []) as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?></ul>
            <?php elseif ($block['type'] === 'quote'): ?>
                <blockquote><p><?= e((string) ($block['text'] ?? '')) ?></p><?php if (!empty($block['source'])): ?><cite><?= e((string) $block['source']) ?></cite><?php endif; ?></blockquote>
            <?php elseif ($block['type'] === 'callout'): ?>
                <aside class="notice"><h2><?= e((string) ($block['title'] ?? '')) ?></h2><p><?= e((string) ($block['text'] ?? '')) ?></p></aside>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</article>

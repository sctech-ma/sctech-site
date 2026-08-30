<?php

declare(strict_types=1);

$blocks = is_array($blocks ?? null) ? $blocks : [];
?>
<div class="prose-blocks">
    <?php foreach ($blocks as $block): ?>
        <?php
        if (!is_array($block)) {
            continue;
        }
        $type = (string) ($block['type'] ?? 'paragraph');
        ?>
        <?php if ($type === 'heading'): ?>
            <?php if (($block['level'] ?? 2) === 3): ?>
                <h3><?= e((string) ($block['text'] ?? '')) ?></h3>
            <?php else: ?>
                <h2><?= e((string) ($block['text'] ?? '')) ?></h2>
            <?php endif; ?>
        <?php elseif ($type === 'paragraph'): ?>
            <p><?= e((string) ($block['text'] ?? '')) ?></p>
        <?php elseif ($type === 'list' && is_array($block['items'] ?? null)): ?>
            <?php if (!empty($block['title'])): ?><h3><?= e((string) $block['title']) ?></h3><?php endif; ?>
            <ul>
                <?php foreach ($block['items'] as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?>
            </ul>
        <?php elseif ($type === 'steps' && is_array($block['items'] ?? null)): ?>
            <ol class="prose-steps">
                <?php foreach ($block['items'] as $index => $item): ?>
                    <li><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><p><?= e((string) $item) ?></p></li>
                <?php endforeach; ?>
            </ol>
        <?php elseif ($type === 'callout'): ?>
            <aside class="prose-callout">
                <span><?= e((string) ($block['label'] ?? ($block['title'] ?? 'À retenir'))) ?></span>
                <p><?= e((string) ($block['text'] ?? '')) ?></p>
            </aside>
        <?php elseif ($type === 'quote'): ?>
            <blockquote><p><?= e((string) ($block['text'] ?? '')) ?></p><?php if (!empty($block['source'])): ?><cite><?= e((string) $block['source']) ?></cite><?php endif; ?></blockquote>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

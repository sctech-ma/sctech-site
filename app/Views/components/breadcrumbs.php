<?php

declare(strict_types=1);

$breadcrumbs = is_array($breadcrumbs ?? null) ? $breadcrumbs : [];
$breadcrumbBase = '';
if (!empty($canonical)) {
    $canonicalParts = parse_url((string) $canonical);
    if (is_array($canonicalParts) && isset($canonicalParts['scheme'], $canonicalParts['host'])) {
        $breadcrumbBase = $canonicalParts['scheme'] . '://' . $canonicalParts['host'];
        if (isset($canonicalParts['port'])) {
            $breadcrumbBase .= ':' . $canonicalParts['port'];
        }
    }
}
?>
<?php if ($breadcrumbs !== []): ?>
    <nav class="breadcrumbs shell" aria-label="Fil d’Ariane">
        <ol>
            <li><a href="/">Accueil</a></li>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li>
                    <?php if ($index === array_key_last($breadcrumbs)): ?>
                        <span aria-current="page"><?= e((string) $crumb['label']) ?></span>
                    <?php else: ?>
                        <a href="<?= e((string) $crumb['href']) ?>"><?= e((string) $crumb['label']) ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php if ($breadcrumbBase !== ''): ?>
        <?php
        $breadcrumbItems = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Accueil',
            'item' => $breadcrumbBase . '/',
        ]];
        foreach ($breadcrumbs as $index => $crumb) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 2,
                'name' => (string) $crumb['label'],
                'item' => $breadcrumbBase . (string) $crumb['href'],
            ];
        }
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
        ?>
        <script type="application/ld+json"<?= isset($nonce) && $nonce !== '' ? ' nonce="' . e((string) $nonce) . '"' : '' ?>><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endif; ?>
<?php endif; ?>

<?php

declare(strict_types=1);

$lang = $lang ?? require dirname(__DIR__, 3) . '/lang/fr.php';
$title = trim((string) ($title ?? '')) !== '' ? (string) $title : 'SCTECH — Logiciels financiers sur mesure';
$description = trim((string) ($description ?? '')) !== ''
    ? (string) $description
    : 'SCTECH conçoit des plateformes et infrastructures financières sur mesure, capables d’intégrer vos règles métier et exigences de conformité.';
$canonical = $canonical ?? null;
$robots = $robots ?? 'index,follow,max-image-preview:large';
$ogType = $ogType ?? 'website';
$canonicalParts = is_string($canonical) && $canonical !== '' ? parse_url($canonical) : false;
$siteOrigin = is_array($canonicalParts) && isset($canonicalParts['scheme'], $canonicalParts['host'])
    ? $canonicalParts['scheme'] . '://' . $canonicalParts['host'] . (isset($canonicalParts['port']) ? ':' . $canonicalParts['port'] : '')
    : '';
$ogImage = trim((string) ($ogImage ?? '')) !== '' ? (string) $ogImage : ($siteOrigin !== '' ? $siteOrigin . '/assets/images/social-preview.png' : null);
$currentPath = $currentPath ?? parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$pageClass = $pageClass ?? '';
$content = $content ?? '';
$assetManifest = is_array($assetManifest ?? null) ? $assetManifest : [];
$assetResolver = isset($asset) && is_callable($asset) ? $asset : null;
$assetUrl = static function (string $key, string $fallback) use ($assetResolver, $assetManifest): string {
    if ($assetResolver !== null) {
        return (string) $assetResolver($key);
    }
    return isset($assetManifest[$key]) ? (string) $assetManifest[$key] : $fallback;
};
$nonceAttribute = isset($nonce) && $nonce !== '' ? ' nonce="' . e((string) $nonce) . '"' : '';
$schemaPayloads = [];
if (isset($schema) && is_array($schema)) {
    $schemaPayloads = array_is_list($schema) && isset($schema[0]) && is_array($schema[0]) ? $schema : [$schema];
} elseif (isset($schema) && is_string($schema)) {
    $decodedSchema = json_decode($schema, true);
    if (is_array($decodedSchema)) {
        $schemaPayloads = [$decodedSchema];
    }
}
?>
<!doctype html>
<html lang="fr" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="<?= e((string) $robots) ?>">
    <meta name="theme-color" content="#F7F4EC">
    <meta name="color-scheme" content="light">
    <?php if (is_string($canonical) && $canonical !== ''): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="<?= e((string) $ogType) ?>">
    <meta property="og:site_name" content="SCTECH">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <?php if (is_string($canonical) && $canonical !== ''): ?><meta property="og:url" content="<?= e($canonical) ?>"><?php endif; ?>
    <?php if ($ogImage !== null): ?>
        <meta property="og:image" content="<?= e((string) $ogImage) ?>">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="SCTECH — logiciels financiers sur mesure">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <?php if ($ogImage !== null): ?><meta name="twitter:image" content="<?= e((string) $ogImage) ?>"><?php endif; ?>
    <link rel="icon" href="/assets/images/favicon.ico" sizes="any">
    <link rel="icon" href="/assets/images/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png" sizes="180x180">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="preload" href="/assets/fonts/manrope-latin.woff2" as="font" type="font/woff2" crossorigin>
    <script<?= $nonceAttribute ?>>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
    <link rel="stylesheet" href="<?= e($assetUrl('site.css', '/assets/css/site.css')) ?>">
    <?php foreach ($schemaPayloads as $schemaPayload): ?>
        <?php if (is_array($schemaPayload)): ?><script type="application/ld+json"<?= $nonceAttribute ?>><?= json_encode($schemaPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="<?= e(trim('public-site ' . (string) $pageClass)) ?>">
    <a class="skip-link" href="#contenu">Aller au contenu principal</a>
    <?php require dirname(__DIR__) . '/components/header.php'; ?>
    <?php if (!empty($flash) && is_array($flash)): ?>
        <div class="flash-region shell" aria-live="polite" aria-atomic="true">
            <?php foreach ($flash as $type => $message): ?><p class="notice notice--<?= e($type === 'success' ? 'success' : 'error') ?>"><?= e((string) $message) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <main id="contenu" tabindex="-1"><?= $content ?></main>
    <?php require dirname(__DIR__) . '/components/footer.php'; ?>
    <script src="<?= e($assetUrl('site.js', '/assets/js/site.js')) ?>" defer></script>
</body>
</html>


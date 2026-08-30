<?php

declare(strict_types=1);

$title = isset($title) ? (string) $title : 'Administration SCTECH';
$content = isset($content) ? (string) $content : '';
$user = is_array($user ?? null) ? $user : null;
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH) ?: '/admin';
$assetManifest = is_array($assetManifest ?? null) ? $assetManifest : [];
$adminStyles = isset($assetManifest['admin.css'])
    ? (string) $assetManifest['admin.css']
    : '/assets/css/admin.css';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="same-origin">
    <title><?= e($title) ?></title>
    <meta name="theme-color" content="#071820">
    <link rel="icon" href="/assets/images/logo.png" type="image/png">
    <link rel="preload" href="/assets/fonts/manrope-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e($adminStyles) ?>">
</head>
<body class="admin-site">
    <a class="skip-link" href="#contenu-admin">Aller au contenu principal</a>
    <header class="admin-header">
        <a class="admin-brand" href="/admin" aria-label="Administration SCTECH — Accueil">SCTECH <span>Administration</span></a>
        <?php if ($user !== null): ?>
            <nav aria-label="Navigation de l’administration">
                <ul class="admin-nav">
                    <?php foreach ([
                        '/admin' => 'Tableau de bord',
                        '/admin/pages' => 'Pages',
                        '/admin/expertises' => 'Solutions',
                        '/admin/secteurs' => 'Segments cibles',
                        '/admin/realisations' => 'Réalisations',
                        '/admin/articles' => 'Articles',
                        '/admin/medias' => 'Médias',
                    ] as $path => $label): ?>
                        <li><a href="<?= e($path) ?>"<?= $currentPath === $path ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <li><a href="/admin/messages"<?= str_starts_with($currentPath, '/admin/messages') ? ' aria-current="page"' : '' ?>>Messages</a></li>
                        <li><a href="/admin/devis"<?= str_starts_with($currentPath, '/admin/devis') ? ' aria-current="page"' : '' ?>>Projets</a></li>
                        <li><a href="/admin/parametres"<?= $currentPath === '/admin/parametres' ? ' aria-current="page"' : '' ?>>Paramètres</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="admin-account">
                <span><?= e((string) ($user['display_name'] ?? $user['email'] ?? '')) ?></span>
                <form method="post" action="/admin/logout">
                    <input type="hidden" name="_csrf" value="<?= e((string) ($logoutCsrf ?? '')) ?>">
                    <button type="submit" class="button button--quiet">Se déconnecter</button>
                </form>
            </div>
        <?php endif; ?>
    </header>
    <main id="contenu-admin" class="admin-main" tabindex="-1">
        <?= $content ?>
    </main>
</body>
</html>

<?php

declare(strict_types=1);

$currentPath = (string) ($currentPath ?? '/');
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$namedPath = static function (string $name) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name);
    }
    return match ($name) {
        'home' => '/', 'solutions.index' => '/solutions', 'expertise' => '/expertise',
        'approach' => '/approche', 'islamic-finance' => '/finance-islamique',
        'about' => '/a-propos', 'insights.index' => '/insights', 'project' => '/demander-un-projet',
        default => '/',
    };
};
$navigation = [
    ['label' => 'Solutions', 'href' => $namedPath('solutions.index')],
    ['label' => 'Expertise', 'href' => $namedPath('expertise')],
    ['label' => 'Approche', 'href' => $namedPath('approach')],
    ['label' => 'Finance islamique', 'href' => $namedPath('islamic-finance')],
    ['label' => 'À propos', 'href' => $namedPath('about')],
    ['label' => 'Insights', 'href' => $namedPath('insights.index')],
];
$isActive = static function (string $href) use ($currentPath): bool {
    return $currentPath === $href || ($href !== '/' && str_starts_with($currentPath, $href . '/'));
};
?>
<header class="site-header" data-site-header>
    <div class="site-header__inner shell">
        <a class="brand" href="<?= e($namedPath('home')) ?>" aria-label="SCTECH — Accueil"><span class="brand__plate"><img src="<?= e(asset('assets/images/logo.png')) ?>" width="1866" height="697" alt="SCTECH"></span></a>
        <nav class="desktop-nav" aria-label="Navigation principale"><ul>
            <?php foreach ($navigation as $item): ?><li><a href="<?= e($item['href']) ?>"<?= $isActive($item['href']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li><?php endforeach; ?>
        </ul></nav>
        <a class="button button--forest button--compact header-cta" href="<?= e($namedPath('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a>
        <details class="mobile-nav" data-mobile-menu>
            <summary aria-label="Ouvrir le menu"><span>Menu</span><span class="mobile-nav__icon" aria-hidden="true"><i></i><i></i></span></summary>
            <div class="mobile-nav__panel"><nav aria-label="Navigation mobile">
                <ul><?php foreach ($navigation as $item): ?><li><a href="<?= e($item['href']) ?>"<?= $isActive($item['href']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?><span aria-hidden="true">↗</span></a></li><?php endforeach; ?></ul>
                <a class="button button--light button--full" href="<?= e($namedPath('project')) ?>">Étudier votre projet</a>
                <p>Casablanca · Maroc–Europe<br><a href="mailto:contact@sctech.ma">contact@sctech.ma</a></p>
            </nav></div>
        </details>
    </div>
</header>

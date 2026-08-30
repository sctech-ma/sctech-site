<?php

declare(strict_types=1);

$year = (int) date('Y');
$routeResolver = isset($routeUrl) && is_callable($routeUrl) ? $routeUrl : null;
$footerPath = static function (string $name, array $parameters = []) use ($routeResolver): string {
    if ($routeResolver !== null) {
        return (string) $routeResolver($name, $parameters);
    }
    return match ($name) {
        'home' => '/', 'solutions.index' => '/solutions',
        'solutions.show' => '/solutions/' . rawurlencode((string) ($parameters['slug'] ?? '')),
        'expertise' => '/expertise', 'approach' => '/approche', 'islamic-finance' => '/finance-islamique',
        'about' => '/a-propos', 'insights.index' => '/insights', 'contact' => '/contact',
        'project' => '/demander-un-projet', 'legal' => '/mentions-legales',
        'privacy' => '/politique-confidentialite', 'cookies' => '/politique-cookies', default => '/',
    };
};
?>
<footer class="site-footer">
    <div class="shell">
        <div class="site-footer__lead">
            <div><p class="eyebrow eyebrow--mint">CONSTRUIRE LE BON SYSTÈME</p><h2>Votre logique financière mérite une architecture qui lui appartient.</h2></div>
            <a class="button button--light" href="<?= e($footerPath('project')) ?>">Étudier votre projet <span aria-hidden="true">↗</span></a>
        </div>
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a class="brand brand--footer" href="<?= e($footerPath('home')) ?>" aria-label="SCTECH — Accueil"><span class="brand__plate"><img src="/assets/images/logo.png" width="1025" height="326" alt="SCTECH"></span></a>
                <p>Logiciels financiers sur mesure, conçus autour de vos règles métier, de vos contrôles et de vos opérations.</p>
                <p>Casablanca, Maroc<br>Collaboration Maroc · Afrique · Europe</p>
            </div>
            <nav aria-label="Solutions"><h3>Solutions</h3><ul>
                <li><a href="<?= e($footerPath('solutions.show', ['slug' => 'plateformes-investissement'])) ?>">Plateformes d’investissement</a></li><li><a href="<?= e($footerPath('solutions.show', ['slug' => 'portails-investisseurs'])) ?>">Portails investisseurs</a></li><li><a href="<?= e($footerPath('solutions.show', ['slug' => 'workflows-conformite'])) ?>">Workflows de conformité</a></li><li><a href="<?= e($footerPath('solutions.show', ['slug' => 'data-reporting'])) ?>">Data & reporting</a></li><li><a href="<?= e($footerPath('solutions.show', ['slug' => 'integrations-financieres'])) ?>">Intégrations financières</a></li>
            </ul></nav>
            <nav aria-label="SCTECH"><h3>SCTECH</h3><ul>
                <li><a href="<?= e($footerPath('expertise')) ?>">Expertise</a></li><li><a href="<?= e($footerPath('approach')) ?>">Approche</a></li><li><a href="<?= e($footerPath('islamic-finance')) ?>">Finance islamique</a></li><li><a href="<?= e($footerPath('about')) ?>">À propos</a></li><li><a href="<?= e($footerPath('insights.index')) ?>">Insights</a></li><li><a href="<?= e($footerPath('contact')) ?>">Contact</a></li>
            </ul></nav>
            <div class="site-footer__contact"><h3>Échangeons</h3><a class="footer-email" href="mailto:contact@sctech.ma">contact@sctech.ma</a><p>Un premier échange sert à comprendre le modèle, les utilisateurs, les règles et l’environnement à intégrer.</p><a class="text-link text-link--light" href="<?= e($footerPath('project')) ?>">Préparer la discussion <span aria-hidden="true">→</span></a></div>
        </div>
        <div class="site-footer__bottom"><p>© <?= e((string) $year) ?> SCTECH. Tous droits réservés.</p><nav aria-label="Informations légales"><a href="<?= e($footerPath('legal')) ?>">Mentions légales</a><a href="<?= e($footerPath('privacy')) ?>">Confidentialité</a><a href="<?= e($footerPath('cookies')) ?>">Cookies</a></nav><span>FR · Casablanca</span></div>
    </div>
</footer>

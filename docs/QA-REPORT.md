# Rapport de validation — SCTECH

Date de validation : 29 août 2026  
Environnement local isolé : PHP 8.2.33, MariaDB 12.3.2 et Chrome headless. Le serveur PHP de développement sert uniquement à la QA locale ; Apache reste la cible de production documentée.

## Verdict

Le redesign **Principes programmables**, le socle PHP, les routes, le CMS, les formulaires sécurisés, la migration additive, les assets hachés et les contrôles de livraison passent la validation locale. La publication réelle reste volontairement bloquée par `launch:check` tant que les informations légales, SMTP, HTTPS/hébergement, validations du logo et preuves publiables ne sont pas fournies.

## Contrôles automatisés

| Contrôle | Résultat final |
|---|---:|
| PHPUnit 11 + MariaDB | 67 tests, 305 assertions, aucun skip ni échec |
| PHP_CodeSniffer / PSR-12 | succès |
| PHPStan 2.1 | zéro erreur |
| Lint PHP 8.2 | 190 fichiers, zéro erreur |
| Composer `validate --strict` | succès |
| Syntaxe JavaScript + build esbuild | succès |
| Playwright | 23 scénarios, succès |
| Axe WCAG 2.2 AA | aucun défaut critique ou sérieux sur 7 pages représentatives |
| Crawl interne | 36 pages, 6 assets, zéro lien ou asset cassé |

La suite couvre les routes nommées et dynamiques, contraintes de slug, HEAD, 404/405, slash canonique, redirections, alias POST historique, sorties échappées, URL dangereuses, CSRF, timing, honeypot, rate limiting, idempotence, pannes base/SMTP, authentification, rôles, expiration de session, brouillons, sitemap, médias, migrations, publication et révocation.

## Lighthouse final

| Page | Performance | Accessibilité | Bonnes pratiques | SEO | LCP | CLS | TBT |
|---|---:|---:|---:|---:|---:|---:|---:|
| Accueil | 98 | 100 | 100 | 100 | 2 416 ms | 0 | 0 ms |
| Solution — workflows de conformité | 98 | 100 | 100 | 100 | 2 405 ms | 0 | 0 ms |
| Finance islamique | 98 | 100 | 100 | 100 | 2 410 ms | 0 | 0 ms |
| Article financier | 98 | 100 | 100 | 100 | 2 262 ms | 0,011 | 0 ms |
| Contact | 97 | 100 | 100 | 100 | 2 409 ms | 0,020 | 116 ms |

Chaque page respecte les portes définies : performance ≥ 90, autres catégories ≥ 95, LCP ≤ 2,5 s, CLS ≤ 0,10 et TBT ≤ 200 ms. Les rapports JSON sont conservés localement sous `storage/cache/lighthouse/` et ne font pas partie de l’archive de production.

## Responsive, clavier et progression

La page d’accueil est vérifiée à 320, 375, 390, 430, 768, 1024, 1280, 1440 et 1920 px : aucun débordement horizontal, ID dupliqué, image cassée ou cible interactive sous 44 px. Les captures de référence couvrent mobile, tablette et bureau.

Le menu natif `<details>` s’ouvre au clavier, se ferme avec Échap et restitue le focus. Sans JavaScript, la navigation et le contenu essentiel restent disponibles. Sous `prefers-reduced-motion: reduce` et sur appareil tactile, le contenu demeure immédiatement visible et les mouvements non essentiels sont désactivés. La console finale ne contient aucune erreur ou alerte.

## Intégrité du positionnement

- Les cinq solutions publiques proviennent exclusivement des clés `solution.*` ; les anciennes offres généralistes sont archivées et filtrées.
- Aucun client, rendement, AUM, volume, métrique, certification, témoignage ou garantie halal/Charia n’est publié.
- Le composant de responsabilité précise que les critères sont définis ou validés par les instances compétentes du client et que SCTECH ne délivre ni avis religieux, ni certification, ni conseil en investissement.
- Aucune réalisation brouillon n’apparaît dans le sitemap ou sur le site ; l’absence de preuve déclenche le panneau de demande de références.
- Trois articles financiers originaux sont publiés, tandis que l’ancienne bibliothèque technique reste accessible sans être mise en avant.

## Intégration, sécurité et données

- La migration `003` et le schéma consolidé couvrent les nouveaux champs projet et `articles.category_key`; l’installation et le seed idempotent sont testés sur MariaDB.
- Les formulaires conservent CSRF, honeypot, jeton temporel signé, catalogue d’options partagé, rate limiting persistant, idempotence, persistance avant notification et retry sans doublon.
- Les scénarios automatisés valident la panne et la reprise SMTP ; aucune délivrabilité externe n’est revendiquée sans credentials de production ni essai Mailpit/SMTP dédié.
- Les réponses applicatives portent CSP à nonce, frame denial, `nosniff`, politique de référent restrictive, Permissions Policy et identifiant de requête. HSTS reste désactivé jusqu’à validation HTTPS réelle.
- Les anciennes URL redirigent vers les routes canoniques ; le POST `/process-contact.php` répond `410` et ne contourne jamais les protections du formulaire.

## Budgets mesurés

| Ressource | Mesure | Budget |
|---|---:|---:|
| CSS public gzip | 9 907 octets | ≤ 18 Ko |
| JavaScript public gzip | 1 085 octets | ≤ 8 Ko |
| Polices locales totales | 48 564 octets | ≤ 160 Ko |
| Charge réseau d’accueil Lighthouse | 234 134 octets | ≈ 350 Ko maximum |

## Portes de lancement restantes

`php bin/console launch:check` échoue intentionnellement dans l’environnement local. Avant publication, SCTECH doit fournir et approuver : identité et forme juridiques, adresse, RC/ICE/IF, téléphone, direction de publication, hébergeur, textes et durées de conservation, version de consentement, revue légale/confidentialité/cookies, credentials SMTP et destinataire, domaine HTTPS, HSTS/CSP HTTPS, confirmation d’hébergement, master vectoriel et droits du logo, ainsi qu’au moins une réalisation réellement vérifiée et publiée si cette exigence de lancement est conservée.

La validation locale ne remplace pas un test d’intrusion, une revue juridique, les essais SPF/DKIM/DMARC et de rebond, une restauration de sauvegarde ou un contrôle sur l’infrastructure HTTPS finale. Tout échec de `launch:check` interdit la mise en ligne.

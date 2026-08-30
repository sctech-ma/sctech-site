# AGENTS.md — règles de contribution SCTECH

Ce dépôt est une application PHP native. Toute modification future doit préserver la lisibilité du socle, le positionnement éditorial prudent et les contrôles de sécurité existants.

## Architecture

- `public/index.php` reste l’unique front controller public.
- Le flux est `Request → middleware → router → controller → service/repository → view → Response`.
- Les contrôleurs adaptent HTTP ; les règles métier vivent dans `Services`, la persistance dans `Repositories`, la validation dans `Validation` et les données d’entrée dans des DTO typés.
- Enregistrer les dépendances scalaires ou les interfaces dans `bootstrap/app.php`. Ne pas introduire de service locator dans le code métier.
- Les routes doivent être nommées, contraintes et testées. Préserver HEAD, 404, 405/`Allow`, slash canonique et redirections historiques.
- Ne pas ajouter de framework, ORM, page builder ou moteur de template arbitraire.

## Contenu

- Français en premier ; les chaînes statiques appartiennent à `lang/fr.php`.
- Utiliser des clés stables et des slugs stricts. La locale doit rester présente pour préparer un futur groupe `/en`.
- N’ajouter aucun client, partenaire, certification, chiffre, témoignage, membre d’équipe ou résultat sans preuve et autorisation.
- Une réalisation exemple reste `draft`. Ne jamais contourner le filtre de publication.
- Les blocs CMS restent en JSON versionné et doivent passer `StructuredBlocksValidator`; aucun HTML ou PHP arbitraire.
- Les pages légales restent `noindex` et clairement marquées tant que les portes de lancement ne sont pas satisfaites.
- Les offres publiques utilisent uniquement les cinq clés `solution.*`; préserver le filtrage repository par préfixe.
- Le composant de responsabilité en finance islamique n’est pas supprimable depuis le CMS. SCTECH traduit des critères validés en logiciel, mais ne délivre ni avis religieux, ni certification, ni conseil en investissement.

## Sécurité

- PDO préparé uniquement ; conserver `ATTR_EMULATE_PREPARES=false`.
- Échapper toute donnée dans les vues avec `e()` et valider les URL avec les helpers dédiés.
- Toute mutation POST exige CSRF, validation explicite et contrôle d’autorisation.
- Ne jamais journaliser mot de passe, token, secret, corps SMTP ou contenu personnel complet.
- Les IP et identités persistées à des fins de limitation doivent rester HMAC-hachées.
- Ne jamais faire confiance à `X-Forwarded-*` hors de `TRUSTED_PROXIES`.
- Conserver persist-before-mail et l’idempotence des formulaires.
- Un média doit rester image JPEG/PNG/WebP vérifiée par MIME réel, taille et dimensions ; le nom stocké est aléatoire et `public/uploads/.htaccess` demeure en place.
- Aucun secret, compte par défaut, installateur public ou inscription ne doit être ajouté.

## Design system

- Concept : **Principes programmables** — surfaces ivoire, rigueur institutionnelle et règles/validations rendues opérables.
- Palette verrouillée : canvas `#F7F4EC`, blanc `#FFFFFF`, sauge `#EAF4EF`, encre `#14211D`, texte secondaire `#5D6A64`, bordure `#D7DED8`, vert SCTECH `#2F9B88`, vert accessible `#17665B`, forêt `#0D302A` et menthe du logo `#6ACBB8`.
- Manrope pour l’interface, IBM Plex Mono pour les labels techniques, toujours locales.
- Base d’espacement 4 px, rayons 2/8/16 px, ombres réservées aux navigations et surfaces réellement élevées.
- Conserver les cibles 44 px, le focus visible, le skip link, les titres logiques et les contrôles natifs.
- Le menu mobile reste un `<details>`. Toute fonction essentielle doit fonctionner sans JavaScript.
- Motion uniquement par transformation/opacité ; tester `prefers-reduced-motion` et les pointeurs tactiles.
- Le logo SCTECH ne doit pas être recoloré, redessiné, recadré ou posé sur une surface incompatible. Demander le master vectoriel avant lancement.

## Qualité

Avant de livrer :

```bash
composer validate --strict
composer test
composer lint
composer analyse
npm run check:js
npm run build
npm run test:crawl
npm run test:e2e
npm run test:lighthouse
php bin/console launch:check
```

- Ajouter des tests pour tout correctif de route, validation, sécurité, autorisation ou persistance.
- Exécuter les tests MariaDB avec une base jetable lorsqu’un dépôt ou une migration change.
- Vérifier navigation clavier aux largeurs 320, 375, 390, 430, 768, 1024, 1280, 1440 et 1920 px.
- Contrôler liens, assets, IDs dupliqués, métadonnées, schémas JSON-LD, journaux PHP et console navigateur.
- Respecter les budgets publics : JS ≤ 8 Ko gzip, CSS ≤ 18 Ko gzip, polices ≤ 160 Ko total, accueil initial ≈ 350 Ko maximum.

## Exploitation

- Toute migration appliquée est immutable ; créer un nouveau fichier au lieu de modifier son checksum.
- `database/schema.sql` doit refléter l’état consolidé des migrations.
- `npm run build` doit produire des assets hachés et mettre à jour le manifeste.
- L’hébergement expose seulement `public/`; ne jamais déplacer `.env`, `vendor`, `storage` ou `database` dans un dossier publiquement accessible.
- HSTS ne s’active qu’après vérification HTTPS réelle.
- `launch:check` ne doit pas être affaibli pour faire passer un environnement incomplet.

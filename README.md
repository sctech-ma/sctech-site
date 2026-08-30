# SCTECH — plateforme fintech sur mesure

Site français de SCTECH, construit en PHP natif autour du concept **Principes programmables**. L’expérience positionne SCTECH comme concepteur de logiciels financiers sur mesure capables de traduire des règles métier, des validations et des exigences de conformité définies par les instances compétentes du client en systèmes opérables et auditables.

Le contenu livré ne présente aucun client, certification, partenaire, résultat ou chiffre d’expérience non vérifié. Les réalisations exemples restent en brouillon et la page publique affiche un panneau de références sur demande tant qu’aucune preuve approuvée n’est publiée.

## Socle technique

- PHP 8.2+ avec `strict_types`, architecture MVC, routeur et injection de dépendances internes.
- PDO natif avec requêtes préparées et émulation désactivée, MySQL 8.0+ ou MariaDB 10.6+.
- Dotenv et PHPMailer comme seules dépendances d’exécution.
- HTML sémantique, CSS moderne et JavaScript vanilla avec amélioration progressive.
- esbuild uniquement en développement ; les ressources hachées sont précompilées pour l’hébergement.
- PHPUnit, PHP_CodeSniffer et PHPStan en développement.

Le point d’entrée est exclusivement [`public/index.php`](public/index.php). En production, le document root Apache doit viser `public/`, jamais la racine du dépôt.

## Installation locale

Prérequis : PHP 8.2+, Composer 2, MariaDB/MySQL et Node.js 20+ pour reconstruire les assets. Extensions PHP : `ctype`, `fileinfo`, `json`, `mbstring`, `pdo_mysql`, `openssl` et `sodium` ; `curl` et `zip` sont recommandées.

```bash
composer install
cp .env.example .env
npm install
npm run build
php bin/console migrate
php bin/console db:seed
```

Créez d’abord la base indiquée par `DB_NAME` et un utilisateur SQL limité à cette base. Renseignez ensuite `.env` avec une clé aléatoire d’au moins 32 caractères :

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Pour créer le premier administrateur, aucun formulaire d’inscription ni compte par défaut n’existe :

```bash
printf '%s' 'Mot-de-passe-temporaire-robuste!' | \
  php bin/console admin:create \
  --email=admin@example.com \
  --name="Administration SCTECH" \
  --role=admin \
  --password-stdin
```

Le mot de passe peut aussi être placé temporairement dans `ADMIN_INITIAL_PASSWORD`. Retirez immédiatement cette variable après la commande. Un mot de passe d’au moins 14 caractères avec minuscule, majuscule, chiffre et symbole est exigé.

Serveur local PHP :

```bash
php -S 127.0.0.1:8080 -t public bin/dev-router.php
```

Le routeur de développement ne remplace pas Apache et n’est pas utilisé en production.

## Variables essentielles

| Groupe | Variables |
|---|---|
| Application | `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`, `APP_TIMEZONE` |
| Base | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` |
| Session | `SESSION_NAME`, `SESSION_IDLE_MINUTES`, `SESSION_ABSOLUTE_HOURS`, `TRUSTED_PROXIES` |
| SMTP | `MAIL_TRANSPORT=smtp`, hôte, port, chiffrement, identifiants, expéditeur et destinataire |
| Formulaires | `FORM_MAIL_REQUIRED`, limites, politique de consentement et durée de conservation |
| Lancement | identité juridique, forme, direction de publication, adresse, RC/ICE/IF, hébergeur, texte de conservation, téléphone, URL sociale et validations de revue/logo |

`FORM_MAIL_REQUIRED=true` est la valeur de production attendue. Une confirmation publique n’est alors affichée qu’après persistance en base **et** notification SMTP. Un incident SMTP laisse le même enregistrement relançable via sa clé d’idempotence ; aucun détail SMTP n’est exposé au visiteur.

## Commandes d’exploitation

```bash
php bin/console migrate       # applique et trace les migrations SQL
php bin/console db:seed       # charge le contenu français sûr et idempotent
php bin/console admin:create  # crée un admin ou un éditeur sans inscription publique
php bin/console cache:clear   # vide uniquement storage/cache
php bin/console housekeeping  # purge technique et applique la conservation configurée des demandes
php bin/console launch:check  # échoue tant que les portes de lancement restent ouvertes
```

Planifiez `housekeeping` quotidiennement avec cron. Avec `LEAD_RETENTION_DAYS=0`, les demandes et leurs métadonnées ne sont jamais supprimées. Avec une valeur positive juridiquement approuvée, la commande supprime dans une transaction les anciens contacts/devis, leurs consentements et leurs métadonnées de workflow, puis affiche les comptes par catégorie.

## Contenu et administration

L’administration est disponible à `/admin/login`. Les rôles sont :

- `editor` : pages, expertises, secteurs, réalisations, articles, médias et prévisualisation ;
- `admin` : mêmes droits, plus paramètres et demandes contenant des données personnelles.

Les contenus suivent `draft`, `published` ou `archived`, une locale, une clé stable, un slug unique par locale, des champs SEO, des dates et une version d’optimistic locking. Les blocs JSON acceptent uniquement `paragraph`, `heading`, `list`, `quote` et `callout` en version 1. Aucun HTML, PHP ou constructeur de page libre n’est exécuté.

Les pages publiques conservent leur composition éditoriale sûre et peuvent recevoir les métadonnées, le hero et les blocs structurés d’un enregistrement publié. Les cinq solutions utilisent exclusivement les clés `solution.*` dans la table `services`; le dépôt filtre ce préfixe afin qu’une ancienne offre généraliste ne puisse pas réapparaître. `/admin/expertises` reste l’URL historique de l’écran renommé **Solutions** et les secteurs sont présentés comme **Segments cibles**. Les champs `eyebrow`, `problem_text`, `positioning_text`, `reading_minutes` et `category_key` sont réellement éditables.

Les routes publiques canoniques sont `/`, `/solutions`, les cinq pages `/solutions/{slug}`, `/expertise`, `/finance-islamique`, `/approche`, `/a-propos`, `/insights`, `/contact` et `/demander-un-projet`. Les anciennes URL restent couvertes par des redirections testées. Le composant précisant le rôle de SCTECH et celui des instances compétentes en finance islamique est fixe et ne peut pas être retiré depuis le CMS.

Les brouillons sont prévisualisés derrière authentification avec `noindex`. Une réalisation ne doit être publiée qu’après accord écrit sur le nom, le contexte, les résultats et les visuels. Voir [`docs/CONTENT.md`](docs/CONTENT.md).

## SMTP

Utilisez un serveur SMTP authentifié. L’adresse du visiteur est placée uniquement dans `Reply-To`; l’enveloppe et l’expéditeur restent ceux du domaine SCTECH. Valeurs typiques :

```dotenv
MAIL_TRANSPORT=smtp
MAIL_HOST=smtp.example.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USER=...
MAIL_PASS=...
MAIL_FROM_ADDRESS=no-reply@sctech.ma
MAIL_FROM_NAME=SCTECH
MAIL_TO_ADDRESS=contact@sctech.ma
FORM_MAIL_REQUIRED=true
```

Testez réellement la réception, SPF, DKIM, DMARC, les rebonds et la boîte destinataire avant le lancement. Le projet ne prétend pas que la délivrabilité externe a été vérifiée sans ces accès.

## Tests et qualité

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
```

Pour activer le test MariaDB optionnel :

```bash
TEST_DB_DSN='mysql:host=127.0.0.1;port=3306;dbname=sctech_test;charset=utf8mb4' \
TEST_DB_USER='sctech_test' TEST_DB_PASS='...' composer test
```

La base de test doit être jetable et déjà migrée. Les contrôles de réception SMTP peuvent utiliser Mailpit localement. `test:e2e` couvre les largeurs 320, 375, 390, 430, 768, 1024, 1280, 1440 et 1920 px, le clavier, le mode sans JavaScript, reduced motion et Axe. `test:lighthouse` applique les seuils de lancement aux cinq pages représentatives.

Les résultats de la validation de livraison sont consignés dans [`docs/QA-REPORT.md`](docs/QA-REPORT.md).

## Déploiement Apache

1. Construire les assets et exécuter les tests en CI ou localement.
2. Installer les dépendances de production : `composer install --no-dev --classmap-authoritative --no-interaction`.
3. Copier le runtime privé complet (`app`, `bin/console`, `bootstrap`, `config`, `database`, `lang`, `routes`, `storage`, `vendor`) hors du dossier web et exposer uniquement `public/`.
4. Donner en écriture au processus PHP uniquement `storage/logs`, `storage/cache` et `public/uploads`.
5. Utiliser `.env.example` comme checklist de lancement, pas comme environnement de production : créer un `.env` privé ou injecter les variables approuvées via l’hébergeur, remplacer tous les placeholders et désactiver le debug.
6. Exécuter `php bin/console migrate`, puis `db:seed` lors de la première installation.
7. Créer le premier administrateur, configurer cron, SMTP, sauvegardes et supervision.
8. Vérifier HTTPS, puis seulement activer HSTS et `CSP_UPGRADE_INSECURE_REQUESTS`.
9. Exécuter `php bin/console launch:check`; un résultat non nul interdit la mise en ligne.

La stratégie de cache immutable des assets, la compression et les redirections historiques sont configurées dans `public/.htaccess`. En hébergement mutualisé, le modèle `deploy/shared-hosting/index.php.example` doit être personnalisé puis renommé en `public_html/index.php` ; le `public/index.php` canonique ne doit pas être copié tel quel. Le détail des dispositions, permissions et exclusions de l’archive de production se trouve dans [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Sauvegardes

- Sauvegarder la base et `public/uploads` ensemble, chiffrés et avec une politique de rétention validée.
- Tester régulièrement une restauration sur une infrastructure isolée.
- Ne jamais sauvegarder `.env` dans une archive publique ; conserver les secrets dans le coffre de l’hébergeur.
- Journaliser les opérations d’administration sans y copier les contenus sensibles des prospects.

## Sécurité et limites réalistes

Le projet fournit CSRF, tokens temporels signés, honeypot, idempotence, limites persistantes HMAC, cookies `HttpOnly`/`SameSite`, régénération de session, expiration idle/absolue, contrôle de rôles, CSP à nonce, en-têtes de sécurité, validation MIME/dimensions/taille, noms de médias aléatoires, stockage non exécutable, échappement contextuel et logs structurés expurgés.

Ces mesures ne constituent ni une certification, ni une garantie de conformité PCI DSS, CNDP, RGPD ou ISO 27001. Les tests d’intrusion, la revue juridique, la configuration du serveur, la gestion des secrets, les sauvegardes et l’exploitation restent des responsabilités de lancement. Voir [`docs/SECURITY.md`](docs/SECURITY.md).

## Portes de lancement connues

Le lancement reste bloqué jusqu’à fourniture et approbation de l’entité juridique, forme et direction de publication, RC/ICE/IF, adresse complète, hébergeur, texte de confidentialité, durée de conservation, version de consentement, téléphone, URL sociale, credentials SMTP, configuration HTTPS/hébergement et au moins une réalisation vérifiée si elle doit être publique. `launch:check` compare aussi chaque migration appliquée à son fichier et à son checksum, vérifie les trois assets hachés (`site.css`, `admin.css`, `site.js`) et exige `LOGO_VECTOR_MASTER_RECEIVED=true` ainsi que `LOGO_RIGHTS_APPROVED=true`.

Le logo PNG repris de `sctech.ma` est conservé visuellement inchangé et affiché uniquement sur des surfaces claires. Demandez avant lancement le master vectoriel original et la confirmation écrite de ses droits d’usage. Les licences des polices locales sont dans `public/assets/fonts/` et récapitulées dans [`docs/ASSET-LICENSES.md`](docs/ASSET-LICENSES.md).

## Dépannage

- **Erreur APP_KEY** : générer une clé aléatoire de 64 caractères hexadécimaux.
- **Erreur base** : vérifier l’existence de la base, les droits de l’utilisateur et `pdo_mysql`.
- **Page sans style** : exécuter `npm install && npm run build` et vérifier `public/assets/build/manifest.json`.
- **Formulaire enregistré mais non confirmé** : contrôler SMTP/Mailpit et `notification_status`; réutiliser la même soumission évite un doublon.
- **Boucle de connexion** : vérifier l’heure serveur, HTTPS, le domaine du cookie et les délais de session.
- **404 Apache** : activer `mod_rewrite`, autoriser `.htaccess` et pointer le vhost sur `public/`.

## Arborescence

```text
app/          contrôleurs, cœur MVC, middleware, DTO, services, dépôts, validation et vues
bootstrap/    composition des dépendances et middleware globaux
config/       configuration typée chargée depuis l’environnement
database/     migrations, schéma consolidé et seeds sûrs
public/       seul document root, assets hachés et uploads non exécutables
resources/    sources CSS et JavaScript
routes/       routes publiques, historiques et administration
storage/      cache et journaux non publics
tests/        tests unitaires, feature et intégration
bin/          console, build et routeur local
docs/         déploiement, sécurité, contenu et licences
```

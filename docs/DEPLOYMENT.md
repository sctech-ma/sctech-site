# Déploiement

## Disposition canonique

```text
/srv/sctech/                 racine privée
  app/
  bin/
    console                  commandes de migration et d’exploitation
  bootstrap/
  config/
  database/                  migrations, schéma de référence et seeds
  lang/                      chaînes françaises chargées à l’exécution
  routes/
  storage/
  vendor/                    dépendances Composer de production
  .env                       secrets et configuration réels, jamais publics
  public/                    DocumentRoot Apache
```

Le vhost doit pointer vers `/srv/sctech/public`. Activez `mod_rewrite`, `mod_headers`, `mod_expires` et `mod_deflate` quand ils sont disponibles. `AllowOverride FileInfo Options` doit autoriser le `.htaccess` fourni.

Commandes de préparation :

```bash
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
php bin/console migrate
php bin/console db:seed
php bin/console launch:check
```

Node et `node_modules` ne sont pas requis sur l’hébergement après le build.

Permissions recommandées : code et `vendor` en lecture seule pour PHP ; écriture limitée à `storage/logs`, `storage/cache` et `public/uploads`. Ces trois répertoires doivent exister avant le premier démarrage, appartenir à l’utilisateur du processus PHP et ne contenir aucun fichier livré hormis leurs fichiers de protection ou de maintien. Interdire l’exécution dans uploads au niveau du vhost en complément du `.htaccess`.

`.env.example` est une liste de contrôle volontairement bloquante : ses valeurs de développement, placeholders et validations à `false` ne constituent pas une configuration de production. Créez un `.env` privé avec les valeurs approuvées ou injectez-les depuis le gestionnaire de secrets de l’hébergeur. Ne renommez jamais simplement `.env.example` en conservant ses valeurs.

## Hébergement mutualisé cPanel (Heberjahiz) avec `public_html`

Cette disposition correspond exactement à votre compte Heberjahiz Plan Standard Linux (`/home/sctec2n2`), où le dépôt Git cPanel est placé dans `repositories/sctech-site` et le DocumentRoot public dans `public_html` :

```text
/home/sctec2n2/
├── repositories/
│   └── sctech-site/         # Répertoire privé du code et dépôt Git cPanel
│       ├── app/
│       ├── bin/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── lang/
│       ├── routes/
│       ├── storage/         # logs/, cache/, sessions/ (écriture PHP)
│       ├── vendor/          # Dépendances Composer de production
│       ├── .cpanel.yml      # Instructions de déploiement automatique cPanel
│       └── .env             # Fichier privé optionnel
└── public_html/             # Racine Web publique exposée par Apache
    ├── assets/              # Assets compilés (build/, fonts/, images/)
    ├── uploads/             # Médias téléversés par l'administration (écriture PHP)
    ├── .htaccess            # Règles Apache, sécurité et réécriture
    ├── index.php            # Front controller déployé depuis public/index.php
    ├── env.php              # Fichier de configuration cPanel (copié depuis env.example.php)
    └── site.webmanifest     # Manifeste PWA
```

### 1. Résolution dynamique du front controller

Le fichier `public/index.php` détecte dynamiquement son environnement d'exécution :
- En local (Laragon) : détecte son parent direct `dirname(__DIR__) . '/bootstrap/app.php'`.
- Sur votre cPanel Heberjahiz : détecte automatiquement `/home/sctec2n2/repositories/sctech-site/bootstrap/app.php` (ou en dossier frère direct `sctech-site/`).
- Optionnellement, la variable d'environnement `APP_ROOT` (ou `SCTECH_APP_ROOT`) peut surcharger ce chemin.

Aucune modification manuelle de chemin n'est requise dans `index.php` lors du déploiement.

### 2. Déploiement via cPanel Git Version Control (`.cpanel.yml`)

Le dépôt Git cPanel est configuré à la racine privée `/home/<cpanel-user>/sctech-site`.
Le fichier `.cpanel.yml` à la racine orchestre le déploiement sécurisé et non destructif des seuls fichiers publics vers `public_html` :

```yaml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/<cpanel-user>/public_html
    - /bin/mkdir -p $DEPLOYPATH/assets
    - /bin/mkdir -p $DEPLOYPATH/uploads
    - /bin/cp public/index.php $DEPLOYPATH/index.php
    - /bin/cp public/.htaccess $DEPLOYPATH/.htaccess
    - /bin/cp public/site.webmanifest $DEPLOYPATH/site.webmanifest
    - /bin/cp -n public/env.example.php $DEPLOYPATH/env.example.php
    - /bin/cp -R public/assets/* $DEPLOYPATH/assets/
    - /bin/cp public/uploads/.htaccess $DEPLOYPATH/uploads/.htaccess
    - /bin/chmod 755 $DEPLOYPATH/assets $DEPLOYPATH/uploads
    - /bin/chmod 644 $DEPLOYPATH/index.php $DEPLOYPATH/.htaccess $DEPLOYPATH/site.webmanifest $DEPLOYPATH/uploads/.htaccess $DEPLOYPATH/env.example.php
```

### 3. Configuration sans terminal (directement dans `public_html`)

Si vous n'avez pas d'accès SSH ni de terminal dans votre cPanel Heberjahiz, vous pouvez configurer l'ensemble du site directement depuis `public_html` via le **Gestionnaire de fichiers (File Manager)** de cPanel :

1. Déployez le projet via cPanel Git Version Control (*Update from Remote* puis *Deploy HEAD Commit*).
2. Dans le Gestionnaire de fichiers cPanel, rendez-vous dans `public_html`.
3. Vous y trouverez le fichier `env.example.php` déployé automatiquement.
4. **Copiez ou renommez** `env.example.php` en **`env.php`** (ou créez un fichier `.env`).
5. Cliquez sur **Modifier (Edit)** sur `env.php` et renseignez vos identifiants MySQL créés dans cPanel :
   ```php
   return [
       'APP_ENV'   => 'production',
       'APP_DEBUG' => 'false',
       'APP_URL'   => 'https://votre-domaine.ma',
       'APP_KEY'   => 'votre-cle-secrete-d-au-moins-32-caracteres',
       'DB_HOST'   => 'localhost',
       'DB_PORT'   => '3306',
       'DB_NAME'   => '<cpanel-user>_sctech',
       'DB_USER'   => '<cpanel-user>_dbuser',
       'DB_PASS'   => 'votre_mot_de_passe_robuste',
   ];
   ```
6. Enregistrez : le site est immédiatement connecté et fonctionnel !

**Sécurité et persistance :**
- **Non écrasé lors des mises à jour :** `.cpanel.yml` ne copie ni n'écrase jamais `env.php` (seul `env.example.php` est synchronisé avec l'option `-n`). Vos identifiants restent donc préservés lors de chaque mise à jour Git.
- **Accès Web interdit :** Le fichier `.htaccess` bloque toute requête Web vers `env.php` (`Require all denied`), et le code PHP refuse l'accès direct si `SCTECH_PUBLIC_PATH` n'est pas initialisé par le front controller.

**Garanties de sécurité de `.cpanel.yml` :**
- Aucun code privé (`app/`, `config/`, `database/`, `bootstrap/`, `storage/`) ni `.git` n'est copié dans `public_html`.
- Le fichier `.env` reste strictement dans `sctech-site/.env` et n'est jamais déployé dans `public_html`.
- Aucune commande de suppression globale (`rm -rf`) n'est exécutée : les fichiers téléversés par les utilisateurs dans `public_html/uploads/` ne sont jamais écrasés ni supprimés lors des mises à jour.

### 3. Workflow de mise à jour dans cPanel

Par défaut sur un hébergement mutualisé cPanel lié à un dépôt distant (GitHub) :
1. Pousser les modifications locales testées sur le dépôt distant (`origin`).
2. Dans le panneau cPanel, accéder à **Git™ Version Control** > cliquer sur **Gérer (Manage)** en face du dépôt `sctech-site`.
3. Sous l'onglet **Pull or Deploy** :
   - Cliquer sur **Update from Remote** (Met à jour le code privé dans `sctech-site` via `git pull`).
   - Cliquer sur **Deploy HEAD Commit** (Exécute les tâches de `.cpanel.yml` pour déployer vers `public_html`).

*Note sur l'automatisation :* Le déploiement ne s'exécute pas automatiquement au simple push Git à moins d'avoir configuré un webhook entrant cPanel. Sur hébergement mutualisé, le workflow manuel en deux clics ("Update from Remote" puis "Deploy HEAD Commit") est la méthode la plus fiable et recommandée car elle permet de contrôler chaque mise en production.

### 4. Gestion des dépendances de production (Composer & Build)

Sur un hébergement mutualisé sans accès SSH ni binaire Composer/Node disponible :
- **Assets front-end :** Exécuter `npm run build` en local avant la livraison afin que `public/assets/build/` contienne les fichiers compilés et `manifest.json`.
- **Dépendances Composer :** Exécuter localement `composer install --no-dev --classmap-authoritative --no-interaction`.
- **Stratégie Git pour cPanel :** Comme `vendor/` et `public/assets/build/` sont ignorés sur la branche de développement `main`, préparez une branche de livraison dédiée (ex: `production` ou `release`) incluant `vendor/` et `public/assets/build/`, ou déployez une archive de livraison conforme à la section [Archive de production](#archive-de-production). Sélectionnez cette branche de livraison dans cPanel Git Version Control.

### 5. Configuration de la base de données dans cPanel

1. Dans cPanel, ouvrir **Bases de données MySQL®** (ou l'Assistant de bases de données).
2. Créer la base de données : cPanel préfixe automatiquement le nom avec votre utilisateur, par ex. `<cpanel-user>_sctech`.
3. Créer l'utilisateur MySQL : par ex. `<cpanel-user>_dbuser` avec un mot de passe fort et aléatoire.
4. Associer l'utilisateur à la base en lui accordant **Tous les privilèges (ALL PRIVILEGES)**.
5. Reporter ces identifiants dans `/home/<cpanel-user>/sctech-site/.env` :
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=<cpanel-user>_sctech
   DB_USER=<cpanel-user>_dbuser
   DB_PASS="votre_mot_de_passe_robuste"
   ```
6. Dans **phpMyAdmin** de cPanel, importer le schéma initial `database/schema.sql` (ou les fichiers `database/migrations/*.sql`) ainsi que les seeds autorisés.

### 6. Version PHP et extensions requises

1. Dans cPanel, ouvrir **Gestionnaire MultiPHP** (MultiPHP Manager) ou **Sélectionner une version de PHP** (PHP Selector).
2. Définir la version de PHP sur **PHP 8.2** ou **PHP 8.3**.
3. Dans la liste des extensions (Extensions PHP), vérifier l'activation des modules obligatoires :
   - `ctype`
   - `fileinfo`
   - `json`
   - `mbstring`
   - `pdo`
   - `pdo_mysql`
4. Paramètres recommandés dans l'éditeur INI : `memory_limit = 256M`, `upload_max_filesize = 8M`, `post_max_size = 10M`, `display_errors = Off`, `log_errors = On`.

### 7. Permissions et répertoires d'écriture

Sous Linux cPanel (suPHP / mod_lsapi / PHP-FPM) :
- Tous les répertoires : `0755` (`drwxr-xr-x`).
- Tous les fichiers : `0644` (`-rw-r--r--`).
- Fichier `.env` privé : `0600` ou `0640` (accessible uniquement par l'utilisateur du compte).
- Répertoires nécessitant les droits d'écriture pour PHP :
  - `/home/<cpanel-user>/sctech-site/storage/logs/` (journal applicatif)
  - `/home/<cpanel-user>/sctech-site/storage/cache/` (cache structurel)
  - `/home/<cpanel-user>/sctech-site/storage/sessions/` (sessions)
  - `/home/<cpanel-user>/public_html/uploads/` (téléversements médias, protégé par `.htaccess`)

### 8. Étapes manuelles indispensables à l'initialisation

1. **Supprimer le fichier d'accueil par défaut de l'hébergeur :**
   Heberjahiz et cPanel placent souvent un fichier `default.html`, `index.html` ou un `index.php` générique dans `public_html`. Supprimez-le ou renommez-le pour garantir que le `index.php` de l'application soit prioritaire.
2. **Créer le fichier `/home/<cpanel-user>/sctech-site/.env` :**
   Copiez `.env.example` vers `/home/<cpanel-user>/sctech-site/.env` via le Gestionnaire de fichiers cPanel et renseignez les valeurs réelles de production (ne jamais exposer ce fichier dans `public_html`).
3. **Vérifier l'identifiant réel `<cpanel-user>` :**
   Dans cPanel, repérez votre nom d'utilisateur cPanel (affiché en haut à droite ou dans la colonne Informations générales) et adaptez la ligne `export DEPLOYPATH=/home/<cpanel-user>/public_html` dans `.cpanel.yml` si vous n'utilisez pas la variable `$HOME`.

### 9. Procédure de reprise en cas d'échec de déploiement

En cas d'erreur lors du déploiement cPanel :
1. **Les uploads ne sont jamais perdus :** Le dossier `public_html/uploads/` n'étant jamais supprimé par `.cpanel.yml`, les médias restent intacts.
2. **La base de données reste intacte :** Le déploiement Git ne modifie pas les tables ni les données MySQL existantes.
3. **Pour restaurer la version précédente :**
   - Dans cPanel Git Version Control, sous l'onglet *Pull or Deploy*, basculez sur le commit ou le tag précédent stable.
   - Cliquez sur **Deploy HEAD Commit** pour réappliquer immédiatement les fichiers publics sains dans `public_html`.
   - En cas d'urgence absolue, les fichiers de `public/` de la version stable peuvent être copiés directement dans `public_html/` via le Gestionnaire de fichiers cPanel.

---

## Configuration locale avec Laragon

Le dépôt local est situé dans :
`C:\laragon\www\sctech-site`

Le site s'ouvre à l'adresse :
`http://sctech-site.test`

### Virtual Host Apache spécifique au projet

Laragon détecte automatiquement les sous-dossiers `public/` lorsqu'il génère ses hôtes virtuels. Pour vous assurer que le DocumentRoot pointe bien vers `public/` :

Fichier : `C:\laragon\etc\apache2\sites-enabled\auto.sctech-site.test.conf`

```apache
<VirtualHost *:80> 
    DocumentRoot "C:/laragon/www/sctech-site/public"
    ServerName sctech-site.test
    ServerAlias *.sctech-site.test
    <Directory "C:/laragon/www/sctech-site/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Application des modifications dans Laragon :**
1. Si le fichier virtuel n'existe pas ou pointe vers `C:/laragon/www/sctech-site` au lieu de `public`, ajustez `DocumentRoot` et `<Directory>` vers `C:/laragon/www/sctech-site/public`.
2. Dans Laragon, cliquez sur le menu contextuel > **Apache** > **Recharger (Reload)**.
3. Vérifiez que `sctech-site.test` est bien résolu vers `127.0.0.1` dans `C:\Windows\System32\drivers\etc\hosts` (Laragon le configure automatiquement au démarrage).

## Archive de production

L’archive de livraison est construite depuis une liste blanche. Elle contient le runtime (`app/`, `bin/console`, `bootstrap/`, `config/`, `database/`, `lang/`, `public/`, `routes/`, les répertoires `storage/` vides requis et `vendor/` installé avec `--no-dev`), ainsi que les fichiers d’exploitation utiles (`.env.example`, `composer.json`, `composer.lock`, `README.md`, `docs/` et `deploy/`). Les assets hachés déjà construits dans `public/assets/build/` font partie de l’archive.

Sous Windows, après `npm ci` et `npm run build`, la commande suivante construit et contrôle cette liste blanche dans `../SCTECH-production-upload.zip` (adaptez les deux chemins vers l’outillage local) :

```powershell
powershell -ExecutionPolicy Bypass -File bin/build-production-archive.ps1 `
  -PhpExecutable C:\chemin\vers\php.exe `
  -ComposerPhar C:\chemin\vers\composer.phar
```

Le script installe Composer dans une zone de staging isolée avec `--no-dev --classmap-authoritative`, vérifie les assets déclarés dans le manifeste, refuse les entrées sensibles ou de développement et affiche le SHA-256 du ZIP final.

Sont exclus explicitement : `.env` et tout secret, `node_modules/`, `resources/`, `tests/`, caches de test ou d’analyse, journaux, fichiers de cache, médias envoyés, répertoires de travail, métadonnées Git/IDE, dépendances Composer de développement et sources temporaires d’archive. Vérifiez la liste du ZIP avant transfert ; aucun fichier absent de la liste blanche ne doit être ajouté par commodité.

## HTTPS et proxy

- Déclarez exclusivement les adresses du reverse proxy dans `TRUSTED_PROXIES`.
- Vérifiez d’abord certificats, redirections et protocole transmis.
- Activez ensuite `HSTS_ENABLED=true` et `CSP_UPGRADE_INSECURE_REQUESTS=true`.
- Ne préchargez pas HSTS sans audit des sous-domaines.

## Cache et compression

Les fichiers hachés reçoivent un cache immutable d’un an. Le HTML et les formulaires ne doivent pas recevoir ce cache. Activez Brotli au niveau du serveur si disponible ; Deflate est déjà documenté dans `.htaccess`.

## Sauvegardes et reprise

Synchronisez sauvegarde SQL et uploads, chiffrez hors site, limitez l’accès et testez la restauration. Conservez une copie du code correspondant à chaque sauvegarde. Planifiez `php bin/console housekeeping` chaque jour et documentez séparément la purge des prospects après validation de la durée de conservation.

## Retour arrière

Déployez dans un nouveau répertoire versionné, exécutez les migrations compatibles, puis basculez le lien/vhost. Ne remplacez jamais une migration déjà appliquée. Pour un rollback applicatif, gardez l’ancienne release et assurez-vous que son code accepte le schéma plus récent.

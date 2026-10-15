# Guide de déploiement cPanel & Retour d'expérience (Zéro Terminal)

Ce guide récapitule l'architecture, la méthode de déploiement et toutes les leçons apprises pour déployer un site PHP moderne (sans framework lourd ou PHP natif MVC) depuis **Laragon (local)** vers un hébergement mutualisé **cPanel (Heberjahiz, etc.) sans accès SSH/Terminal**.

---

## 1. Comprendre l'architecture cPanel : Privé vs Public

Sur un hébergement Linux mutualisé cPanel, la racine de votre compte est `/home/<cpanel-user>/`.

| Emplacement | Rôle | Accessibilité Web |
| :--- | :--- | :--- |
| `/home/<user>/repositories/<projet>/` | **Code source privé & Git** : `app/`, `config/`, `database/`, `vendor/`, etc. | ❌ Bloqué / Inaccessible (Sécurité maximale) |
| `/home/<user>/public_html/` | **Racine Web publique** : `index.php`, `.htaccess`, `assets/`, `env.php` |  Accessible via `https://votredomaine.ma/` |

> ⚠️ **Règle d'or :** Ne mettez **JAMAIS** le code privé de l'application dans `public_html`. Seul le contenu du dossier `public/` doit être exposé.

---

## 2. Les 3 pièges critiques & leçons apprises

### Piège 1 : L'erreur HTTP 500 et le dossier `vendor/` manquant
- **Le problème :** Dans Git, le dossier `vendor/` est exclu via `.gitignore`. Quand cPanel clone ou tire le dépôt depuis GitHub, le dossier `vendor/` n'existe pas sur le serveur.
- **La conséquence :** Dès que `index.php` se lance, la ligne `require $root . '/vendor/autoload.php'` déclenche une erreur PHP fatale immédiate (`HTTP ERROR 500`), visible dans le fichier `public_html/error_log`.
- **La solution (sans terminal) :** 
  Sur votre machine locale, compressez le dossier `vendor` en `vendor.zip`. Téléversez `vendor.zip` dans `/home/<user>/repositories/<projet>/` via le Gestionnaire de fichiers cPanel et cliquez sur **Extraire (Extract)**.

### Piège 2 : Le bouton « Deploy HEAD Commit » grisé (The system cannot deploy)
cPanel affiche cet avertissement pour deux raisons :
1. **La syntaxe de `.cpanel.yml` :**
   - **Interdiction des jokers :** Ne jamais utiliser d'astérisque `*` (ex: `cp -R assets/*`). Le validateur cPanel rejette le fichier.
   - **Slash final obligatoire :** `export DEPLOYPATH=/home/<user>/public_html/` doit impérativement se terminer par un slash `/`.
   - **Pas de commentaires complexes ou caractères accentués** dans `.cpanel.yml`.
2. **Un working-tree "sale" (uncommitted changes) :**
   - Si vous avez créé, modifié ou déposé un fichier manuellement dans `/home/<user>/repositories/<projet>/` (par exemple un `error_log` ou un faux `env.php`), Git sur cPanel détecte des modifications locales non commitées et refuse de déployer.
   - **Solution :** Supprimez tout fichier étranger dans ce dossier pour qu'il soit parfaitement synchronisé avec le commit Git.

### Piège 3 : La configuration sans terminal (`public_html/env.php`)
- Sur cPanel partagé, éditer un fichier `.env` caché dans le répertoire privé sans SSH est fastidieux.
- **La solution SCTECH :** Le front controller charge automatiquement `public_html/env.php` si présent.
- **Sécurité :** `public_html/.htaccess` contient une règle stricte `FilesMatch` qui refuse formellement l'accès Web à `env.php`, et le fichier PHP lui-même commence par `if (!defined('SCTECH_BOOTSTRAPPED')) { exit(403); }`.

---

## 3. Guide pas-à-pas pour un nouveau projet

### Étape A — En local (avant de déployer)

1. **Compiler les assets front-end :**
   ```bash
   npm run build
   ```
   *(S'assurer que `public/assets/build/` est bien commité dans Git).*

2. **Créer l'archive des dépendances Composer :**
   ```bash
   tar -a -cf vendor.zip vendor
   ```
   *(Ou via clic droit Windows > Compresser en fichier ZIP).*

3. **Vérifier le fichier `.cpanel.yml` à la racine du projet :**
   ```yaml
   ---
   deployment:
     tasks:
       - export DEPLOYPATH=/home/<votre_user_cpanel>/public_html/
       - /bin/cp public/index.php $DEPLOYPATH
       - /bin/cp public/.htaccess $DEPLOYPATH
       - /bin/cp public/site.webmanifest $DEPLOYPATH
       - /bin/cp -n public/env.example.php $DEPLOYPATH
       - /bin/cp -R public/assets $DEPLOYPATH
       - /bin/mkdir -p $DEPLOYPATH/uploads
       - /bin/cp public/uploads/.htaccess $DEPLOYPATH/uploads/
       - /bin/chmod 755 $DEPLOYPATH/assets $DEPLOYPATH/uploads
       - /bin/chmod 644 $DEPLOYPATH/index.php $DEPLOYPATH/.htaccess $DEPLOYPATH/site.webmanifest $DEPLOYPATH/uploads/.htaccess
   ```

4. **Pousser sur GitHub / GitLab :**
   ```bash
   git add .
   git commit -m "Prepare production deployment"
   git push origin main
   ```

---

### Étape B — Sur cPanel (Configuration initiale)

#### 1. Créer la base de données MySQL
1. Dans cPanel, ouvrir **Bases de données MySQL®**.
2. Créer une base de données (ex : `sctec2n2_sctech`).
3. Créer un utilisateur MySQL avec un mot de passe fort (ex : `sctec2n2_dbuser`).
4. **Associer l'utilisateur à la base** et cocher **Tous les privilèges (ALL PRIVILEGES)**.
5. Dans **phpMyAdmin**, ouvrir la base créée et importer le fichier `database/schema.sql`.

#### 2. Configurer Git Version Control
1. Dans cPanel, ouvrir **Git™ Version Control**.
2. Cliquer sur **Create**.
3. Renseigner :
   - **Clone URL** : L'URL HTTPS de votre dépôt Git (ex: GitHub).
   - **Repository Path** : `repositories/<nom-du-projet>` (ex : `repositories/sctech-site`).
   - **Repository Name** : Nom du projet.
4. Cliquer sur **Create** et attendre la fin du clonage initial.

#### 3. Déposer le dossier `vendor` (Obligatoire pour PHP)
1. Ouvrir le **Gestionnaire de fichiers (File Manager)**.
2. Aller dans `/home/<user>/repositories/<nom-du-projet>/`.
3. Cliquer sur **Téléverser (Upload)** et envoyer votre fichier local `vendor.zip`.
4. Faire un clic droit sur `vendor.zip` > **Extraire (Extract)**.
5. *(Optionnel)* Supprimer le `vendor.zip` après extraction.

#### 4. Déployer les fichiers publics vers `public_html`
1. Retourner dans cPanel > **Git™ Version Control** > **Manage** en face du dépôt.
2. Sous l'onglet **Pull or Deploy** :
   - Cliquer sur **Update from Remote**.
   - Cliquer sur **Deploy HEAD Commit**.
3. Les fichiers (`index.php`, `.htaccess`, `assets/`, `site.webmanifest`) sont automatiquement copiés dans `public_html`.

#### 5. Configurer les variables d'environnement (`env.php`)
1. Dans le **Gestionnaire de fichiers**, aller dans **`public_html`**.
2. Faire un clic droit sur `env.example.php` > **Copier (Copy)** vers **`env.php`**.
3. Faire un clic droit sur `env.php` > **Modifier (Edit)** :
   ```php
   return [
       'APP_NAME'      => 'MonSite',
       'APP_ENV'       => 'production',
       'APP_DEBUG'     => 'false',
       'APP_URL'       => 'https://votredomaine.ma',
       'APP_KEY'       => 'cle-secrete-d-au-moins-32-caracteres',
       'DB_HOST'       => 'localhost',
       'DB_PORT'       => '3306',
       'DB_NAME'       => '<user>_nomdelabase',
       'DB_USER'       => '<user>_nomutilisateur',
       'DB_PASS'       => 'votre_mot_de_passe_robuste',
   ];
   ```
4. Enregistrer. Le site est opérationnel !

---

## 4. Workflow des mises à jour futures (en 2 clics)

Pour mettre à jour le site après une modification de code :

1. **En local :**
   ```bash
   npm run build   # si modification CSS/JS
   git add .
   git commit -m "Nouvelle fonctionnalité"
   git push origin main
   ```
2. **Sur cPanel :**
   - Aller dans **Git™ Version Control** > **Manage**.
   - Onglet **Pull or Deploy** > Cliquer sur **Update from Remote**.
   - Cliquer sur **Deploy HEAD Commit**.
   - C'est tout ! Vos identifiants dans `public_html/env.php` et vos uploads dans `public_html/uploads/` ne sont jamais écrasés.

---

## 5. Extraits de code réutilisables

### Le front controller résolveur universel (`public/index.php`)
Permet au même fichier de fonctionner en local (`Laragon`) ET sur cPanel (`public_html` séparé) sans modifier une seule ligne :

```php
<?php

declare(strict_types=1);

define('SCTECH_PUBLIC_PATH', __DIR__);

// Liste ordonnée des chemins candidats vers bootstrap/app.php
$candidatePaths = [
    dirname(__DIR__) . '/bootstrap/app.php',                                  // Local Laragon
    dirname(__DIR__) . '/repositories/sctech-site/bootstrap/app.php',        // cPanel standard
    dirname(__DIR__) . '/sctech-site/bootstrap/app.php',                     // cPanel dossier direct
];

$bootstrapFile = null;
foreach ($candidatePaths as $candidate) {
    if (is_file($candidate)) {
        $bootstrapFile = $candidate;
        break;
    }
}

if ($bootstrapFile === null) {
    http_response_code(500);
    exit('Erreur de configuration : Impossible de localiser bootstrap/app.php.');
}

$router = require $bootstrapFile;
$response = $router->dispatch(SCTech\Core\Request::fromGlobals());
$response->send();
```

### Protection `.htaccess` pour `public_html`
Bloque immédiatement toute tentative de lire `env.php`, `.env`, les logs ou les fichiers SQL depuis un navigateur :

```apache
# Bloquer tout fichier sensible ou de configuration
<FilesMatch "(^\.|\.(?:env|ini|log|sql|bak|dist|md|lock|json|ya?ml|neon)$|^env(\.example)?\.php$)">
    Require all denied
</FilesMatch>

# Routage Front Controller vers index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

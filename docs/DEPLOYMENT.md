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

## Hébergement mutualisé avec `public_html`

Lorsque le panneau ne permet pas de changer le document root :

```text
/home/account/sctech/        racine privée
  app/ bin/ bootstrap/ config/ database/ lang/ routes/ storage/ vendor/
  .env
/home/account/public_html/   contenu web issu de public/
```

Copiez dans `public_html/` les éléments de `public/` à l’exception de son `index.php` canonique, puis copiez `public/.htaccess` sous `public_html/.htaccess`. Le point d’entrée mutualisé doit provenir de `deploy/shared-hosting/index.php.example` : adaptez son chemin absolu vers la racine privée, vérifiez-le, puis renommez cette copie en `public_html/index.php`. Ne copiez pas `public/index.php` tel quel : son chemin relatif est conçu uniquement pour la disposition canonique et ne peut pas charger une application séparée dans `/home/account/sctech`.

Le dossier privé doit notamment contenir `database/` et `bin/console` afin que migrations, seeds, housekeeping et contrôles de lancement restent exécutables depuis `/home/account/sctech`. Les chemins d’assets restent `/assets/...` car les ressources compilées se trouvent dans `public_html/assets`. Les seuls chemins d’écriture restent `storage/logs`, `storage/cache` et `public_html/uploads`.

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

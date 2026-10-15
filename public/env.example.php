<?php

declare(strict_types=1);

/**
 * Configuration SCTECH pour hébergement mutualisé cPanel (sans terminal).
 *
 * GUIDE D'UTILISATION DANS CPANEL :
 * 1. Dans cPanel, ouvrez le "Gestionnaire de fichiers" (File Manager) et entrez dans public_html.
 * 2. Copiez ou renommez ce fichier en : env.php
 * 3. Modifiez env.php avec vos identifiants MySQL créés dans cPanel (Bases de données MySQL).
 * 4. Enregistrez : le site est immédiatement opérationnel, sans aucune commande terminal !
 *
 * NOTE DE SÉCURITÉ :
 * Ce fichier est strictement protégé contre tout accès Web direct par la directive
 * FilesMatch du fichier .htaccess ainsi que par le contrôle PHP ci-dessous.
 */

if (!defined('SCTECH_PUBLIC_PATH') && !defined('SCTECH_BOOTSTRAPPED')) {
    http_response_code(403);
    exit('Accès direct refusé.');
}

return [
    // Paramètres généraux
    'APP_NAME'      => 'SCTECH',
    'APP_ENV'       => 'production',
    'APP_DEBUG'     => 'false',
    'APP_URL'       => 'https://sctech.ma',
    'APP_KEY'       => '3c8597d6c6ccbde40c9c8d6377b9f99d068a5c7eb727b8b0e797dc5d22e5938e',
    'APP_TIMEZONE'  => 'UTC',
    'APP_LOCALE'    => 'fr',

    // Base de données MySQL cPanel
    // Créez la base et l'utilisateur dans l'outil "Bases de données MySQL" de cPanel.
    // N'oubliez pas d'associer l'utilisateur à la base avec "Tous les privilèges".
    // Sur votre compte cPanel Heberjahiz, le préfixe est "sctec2n2" :
    'DB_HOST'       => 'localhost',
    'DB_PORT'       => '3306',
    'DB_NAME'       => 'sctec2n2_sctech',
    'DB_USER'       => 'sctec2n2_dbuser',
    'DB_PASS'       => 'votre_mot_de_passe_robuste',
    'DB_CHARSET'    => 'utf8mb4',

    // Envoi des e-mails (formulaires de contact et devis)
    // Par défaut 'log' (écrit dans storage/logs/app.log pour tester sans bloquer).
    // Pour activer l'envoi SMTP réel avec votre boîte mail cPanel :
    'MAIL_TRANSPORT'    => 'log',
    'MAIL_HOST'         => 'localhost',
    'MAIL_PORT'         => '587',
    'MAIL_ENCRYPTION'   => 'tls',
    'MAIL_USER'         => '',
    'MAIL_PASS'         => '',
    'MAIL_FROM_ADDRESS' => 'no-reply@sctech.ma',
    'MAIL_FROM_NAME'    => 'SCTECH',
    'MAIL_TO_ADDRESS'   => 'contact@sctech.ma',
    'FORM_MAIL_REQUIRED'=> 'false',
];

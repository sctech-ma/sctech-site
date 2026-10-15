<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'SCTECH'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', 'https://sctech.ma'), '/'),
    'key' => (string) env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'fr'),
    'root' => dirname(__DIR__),
    'public_path' => (string) env(
        'PUBLIC_PATH',
        defined('SCTECH_PUBLIC_PATH')
            ? (string) constant('SCTECH_PUBLIC_PATH')
            : (is_dir(dirname(__DIR__, 2) . '/public_html')
                ? dirname(__DIR__, 2) . '/public_html'
                : (is_dir(dirname(__DIR__, 3) . '/public_html')
                    ? dirname(__DIR__, 3) . '/public_html'
                    : dirname(__DIR__) . '/public'))
    ),
    'view_path' => dirname(__DIR__) . '/app/Views',
    'storage_path' => dirname(__DIR__) . '/storage',
];

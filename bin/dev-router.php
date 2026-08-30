<?php

declare(strict_types=1);

$public = realpath(dirname(__DIR__) . '/public');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$candidate = $public !== false && is_string($path)
    ? realpath($public . DIRECTORY_SEPARATOR . ltrim($path, '/'))
    : false;

if ($public !== false && $candidate !== false && is_file($candidate)) {
    $prefix = rtrim($public, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (str_starts_with($candidate, $prefix)) {
        return false;
    }
}

require dirname(__DIR__) . '/public/index.php';

<?php

declare(strict_types=1);

use SCTech\Helpers\Escaper;
use SCTech\Helpers\Url;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false) {
            return $default instanceof Closure ? $default() : $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        $normalized = strtolower($trimmed);

        return match ($normalized) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => preg_match('/^(["\']).*\1$/s', $trimmed) === 1
                ? substr($trimmed, 1, -1)
                : $value,
        };
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Escaper::html($value);
    }
}

if (!function_exists('safe_url')) {
    function safe_url(?string $value, string $fallback = '#'): string
    {
        return Url::safeHref($value, $fallback);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return Url::to($path, (string) env('APP_URL', ''));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = (string) env('ASSET_URL', env('APP_URL', ''));

        return Url::asset($path, $base);
    }
}

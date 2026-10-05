<?php

declare(strict_types=1);

namespace SCTech\Helpers;

final class Url
{
    /** @param list<string> $allowedHosts */
    public static function isSafeRedirect(string $target, array $allowedHosts = []): bool
    {
        if ($target === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $target) === 1) {
            return false;
        }

        if (str_starts_with($target, '/') && !str_starts_with($target, '//')) {
            return true;
        }

        $parts = parse_url($target);
        if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        $allowedHosts = array_map(static fn (string $item): string => strtolower(rtrim($item, '.')), $allowedHosts);

        return in_array($host, $allowedHosts, true);
    }

    public static function safeHref(?string $target, string $fallback = '#'): string
    {
        if ($target === null) {
            return $fallback;
        }

        $target = trim($target);
        if ($target === '' || preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return $fallback;
        }

        if (str_starts_with($target, '/') && !str_starts_with($target, '//')) {
            return $target;
        }

        if (str_starts_with($target, '#') || str_starts_with($target, '?')) {
            return $target;
        }

        $scheme = strtolower((string) parse_url($target, PHP_URL_SCHEME));
        if ($scheme === '') {
            return !str_starts_with($target, '//') && !str_contains($target, '\\') ? $target : $fallback;
        }

        if (in_array($scheme, ['http', 'https'], true)) {
            $parts = parse_url($target);
            return is_array($parts)
                && isset($parts['host'])
                && $parts['host'] !== ''
                && !isset($parts['user'], $parts['pass'])
                ? $target
                : $fallback;
        }

        if ($scheme === 'mailto') {
            $address = substr($target, 7);
            $address = explode('?', $address, 2)[0];
            return filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? $target : $fallback;
        }

        if ($scheme === 'tel') {
            return preg_match('/^tel:\+?[0-9(). -]{5,30}$/D', $target) === 1 ? $target : $fallback;
        }

        return $fallback;
    }

    private static string $basePath = '';

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    public static function to(string $path = '', ?string $base = null): string
    {
        if (self::isAbsoluteHttpUrl($path)) {
            return $path;
        }

        if (self::$basePath !== '') {
            $base = self::$basePath;
        } else {
            $base = rtrim((string) ($base ?? ''), '/');
        }

        $path = ltrim($path, '/');

        return $path === '' ? ($base === '' ? '/' : $base . '/') : $base . '/' . $path;
    }

    public static function asset(string $path, ?string $base = null): string
    {
        $path = ltrim($path, '/');
        if (str_contains($path, '..') || preg_match('/[\x00-\x1F\x7F\\\\]/', $path) === 1) {
            return '#';
        }

        return self::to($path, $base);
    }

    public static function slug(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        if (function_exists('transliterator_transliterate')) {
            $value = (string) transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
        } else {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            $value = $transliterated !== false ? strtolower($transliterated) : $value;
        }

        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private static function isAbsoluteHttpUrl(string $value): bool
    {
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}

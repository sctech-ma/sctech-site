<?php

declare(strict_types=1);

namespace SCTech\Helpers;

use JsonException;
use Stringable;

final class Escaper
{
    public static function html(mixed $value): string
    {
        return htmlspecialchars(self::stringify($value), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function attribute(mixed $value): string
    {
        return self::html($value);
    }

    public static function href(?string $value, string $fallback = '#'): string
    {
        return self::attribute(Url::safeHref($value, $fallback));
    }

    /** @throws JsonException */
    public static function javascript(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
        );
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return '';
    }
}

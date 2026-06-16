<?php

namespace App\Support;

class Locales
{
    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return collect(config('app.supported_locales', ['en']))
            ->filter(fn (mixed $locale): bool => is_string($locale) && $locale !== '')
            ->values()
            ->all();
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    public static function fallback(): string
    {
        $fallbackLocale = config('app.fallback_locale', 'en');

        if (is_string($fallbackLocale) && self::isSupported($fallbackLocale)) {
            return $fallbackLocale;
        }

        return 'en';
    }
}

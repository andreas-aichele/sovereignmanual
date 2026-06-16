<?php

namespace App\Support;

use App\Enums\Language;

class Locales
{
    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return Language::values();
    }

    public static function isSupported(string $locale): bool
    {
        return Language::fromLocale($locale) !== null;
    }

    public static function fallback(): string
    {
        $fallbackLocale = config('app.fallback_locale', Language::fallback()->value);

        return is_string($fallbackLocale)
            ? (Language::fromLocale($fallbackLocale)?->value ?? Language::fallback()->value)
            : Language::fallback()->value;
    }
}

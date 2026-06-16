<?php

namespace App\Enums;

enum Language: string
{
    case English = 'en';
    case German = 'de';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fallback(): self
    {
        return self::English;
    }

    public static function fromLocale(string|self $locale): ?self
    {
        if ($locale instanceof self) {
            return $locale;
        }

        return self::tryFrom($locale);
    }
}

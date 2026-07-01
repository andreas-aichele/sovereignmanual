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

    public function englishName(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function nativeName(): string
    {
        return match ($this) {
            self::English => 'English',
            self::German => 'Deutsch',
        };
    }

    public function openGraphLocale(): string
    {
        return match ($this) {
            self::English => 'en_US',
            self::German => 'de_DE',
        };
    }

    public static function fromLocale(string|self $locale): ?self
    {
        if ($locale instanceof self) {
            return $locale;
        }

        return self::tryFrom($locale);
    }
}

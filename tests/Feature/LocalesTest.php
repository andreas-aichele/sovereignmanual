<?php

use App\Enums\Language;
use App\Support\Locales;

test('supported locales are read from language enum', function () {
    config([
        'app.fallback_locale' => Language::German->value,
    ]);

    expect(Locales::supported())->toBe(Language::values())
        ->and(Locales::isSupported(Language::German->value))->toBeTrue()
        ->and(Locales::isSupported('fr'))->toBeFalse()
        ->and(Locales::fallback())->toBe(Language::German->value);
});

test('fallback locale defaults to english when config locale is unsupported', function () {
    config([
        'app.fallback_locale' => 'fr',
    ]);

    expect(Locales::fallback())->toBe(Language::English->value);
});

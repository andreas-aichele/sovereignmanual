<?php

use App\Support\Locales;

test('supported locales are read from application configuration', function () {
    config([
        'app.locale' => 'de',
        'app.supported_locales' => ['en', 'de', '', null],
    ]);

    expect(Locales::supported())->toBe(['en', 'de'])
        ->and(Locales::isSupported('de'))->toBeTrue()
        ->and(Locales::isSupported('fr'))->toBeFalse()
        ->and(Locales::fallback())->toBe('de');
});

test('fallback locale defaults to english when config locale is unsupported', function () {
    config([
        'app.locale' => 'fr',
        'app.supported_locales' => ['en', 'de'],
    ]);

    expect(Locales::fallback())->toBe('en');
});

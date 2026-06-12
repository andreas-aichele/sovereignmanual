<?php

test('magazine index is the public start page', function () {
    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertSee('Sovereign Manual Magazine');
});

test('german magazine index is the localized public start page', function () {
    $this->get(route('magazine.de.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertSee('Noch keine veröffentlichten Artikel.');
});

test('magazine article urls are available', function () {
    expect(route('magazine.show', 'example', absolute: false))->toBe('/magazine/example');
    expect(route('magazine.de.show', 'beispiel', absolute: false))->toBe('/de/magazine/beispiel');
});

test('frontend entry is a blade asset', function () {
    $contents = file_get_contents(resource_path('js/app.js'));

    expect($contents)->not->toContain('createInertiaApp');
});

test('magazine localization strings live in language files', function () {
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));
    $englishTranslations = require lang_path('en/magazine.php');
    $germanTranslations = require lang_path('de/magazine.php');

    expect(lang_path('en/magazine.php'))->toBeReadableFile()
        ->and(lang_path('de/magazine.php'))->toBeReadableFile()
        ->and($englishTranslations)->toHaveKeys(['index', 'show', 'categories', 'meta', 'routes'])
        ->and($germanTranslations)->toHaveKeys(['index', 'show', 'categories', 'meta', 'routes'])
        ->and($controller)->not->toContain("locale === 'de'")
        ->and($controller)->not->toContain('Zurück zum Magazin')
        ->and($controller)->not->toContain('Back to magazine')
        ->and($controller)->not->toContain('Neuer Artikel')
        ->and($controller)->not->toContain('Latest article');
});

test('magazine headings can break long words', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $css = file_get_contents(resource_path('css/background.css'));

    expect($index)->toContain('wrap-anywhere')
        ->and($index)->toContain('text-4xl')
        ->and($index)->toContain('text-3xl')
        ->and($index)->toContain('text-xl')
        ->and($show)->toContain('wrap-anywhere')
        ->and($show)->toContain('text-4xl')
        ->and($css)->toContain('overflow-wrap: anywhere;');
});

test('magazine article layout keeps public container width and readable article content', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));

    expect($index)->toContain('max-w-6xl px-4 py-10 sm:px-6 lg:px-8')
        ->and($show)->toContain('max-w-6xl px-4 py-10 sm:px-6 lg:px-8')
        ->and($show)->toContain('header class="max-w-4xl"')
        ->and($show)->toContain('content-body mt-10 max-w-4xl');
});

test('magazine article tables have readable cell spacing', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('.content-body table')
        ->and($css)->toContain('overflow-x-auto')
        ->and($css)->toContain('.content-body th,')
        ->and($css)->toContain('.content-body td')
        ->and($css)->toContain('px-4 py-3');
});

test('public magazine frontend does not link login admin or unsplash', function () {
    $publicFiles = [
        resource_path('views/components/public-nav.blade.php'),
        resource_path('views/magazine/index.blade.php'),
        resource_path('views/magazine/show.blade.php'),
    ];

    foreach ($publicFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('/login')
            ->and($contents)->not->toContain('Admin')
            ->and($contents)->not->toContain('unsplash');
    }
});

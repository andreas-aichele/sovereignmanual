<?php

use Inertia\Testing\AssertableInertia;

test('magazine index is the public start page', function () {
    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Index')
            ->where('locale', 'en')
            ->where('meta.title', 'Sovereign Manual Magazine'));
});

test('german magazine index is the localized public start page', function () {
    $this->get(route('magazine.de.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Index')
            ->where('locale', 'de')
            ->where('copy.featured', 'Ausgewählte Transmission')
            ->where('copy.read', 'Artikel lesen')
            ->where('copy.empty', 'Noch keine veröffentlichten Artikel.'));
});

test('magazine article urls are available', function () {
    expect(route('magazine.show', 'example', absolute: false))->toBe('/magazine/example');
    expect(route('magazine.de.show', 'beispiel', absolute: false))->toBe('/de/magazine/beispiel');
});

test('public pages are not wrapped in the starterkit app layout', function () {
    $contents = file_get_contents(resource_path('js/app.ts'));

    expect($contents)->toContain("case name.startsWith('Magazine/'):")
        ->and($contents)->not->toContain("case name === 'Home':")
        ->and($contents)->not->toContain("case name === 'Welcome':");
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
        ->and($controller)->not->toContain('Zurück ins Archiv')
        ->and($controller)->not->toContain('Back to archive')
        ->and($controller)->not->toContain('Ausgewählte Transmission')
        ->and($controller)->not->toContain('Featured transmission');
});

test('magazine headings can break long words', function () {
    $index = file_get_contents(resource_path('js/pages/Magazine/Index.svelte'));
    $show = file_get_contents(resource_path('js/pages/Magazine/Show.svelte'));
    $css = file_get_contents(resource_path('css/background.css'));

    expect($index)->toContain('wrap-anywhere text-4xl')
        ->and($index)->toContain('wrap-anywhere text-3xl')
        ->and($index)->toContain('wrap-anywhere text-xl')
        ->and($show)->toContain('wrap-anywhere text-4xl')
        ->and($css)->toContain('overflow-wrap: anywhere;');
});

test('public magazine frontend does not link login admin or unsplash', function () {
    $publicFiles = [
        resource_path('js/components/PublicNav.svelte'),
        resource_path('js/pages/Magazine/Index.svelte'),
        resource_path('js/pages/Magazine/Show.svelte'),
    ];

    foreach ($publicFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('/login')
            ->and($contents)->not->toContain('Admin')
            ->and($contents)->not->toContain('unsplash');
    }
});

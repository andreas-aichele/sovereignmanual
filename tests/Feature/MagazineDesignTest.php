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
            ->where('copy.read', 'Artikel lesen'));
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

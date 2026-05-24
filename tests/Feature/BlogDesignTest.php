<?php

use Inertia\Testing\AssertableInertia;

test('home page renders the synthwave brand entry point', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('locale', 'en')
            ->where('meta.title', 'Sovereign Manual'));
});

test('german home page renders localized public copy', function () {
    $this->get(route('home.de'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('locale', 'de')
            ->where('copy.primaryCta', 'Archiv betreten'));
});

test('public pages are not wrapped in the starterkit app layout', function () {
    $contents = file_get_contents(resource_path('js/app.ts'));

    expect($contents)->toContain("case name === 'Home':")
        ->and($contents)->toContain("case name.startsWith('Blog/'):");
});

test('public blog frontend does not link login admin or unsplash', function () {
    $publicFiles = [
        resource_path('js/components/PublicNav.svelte'),
        resource_path('js/pages/Blog/Index.svelte'),
        resource_path('js/pages/Blog/Show.svelte'),
        resource_path('js/pages/Home.svelte'),
    ];

    foreach ($publicFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('/login')
            ->and($contents)->not->toContain('Admin')
            ->and($contents)->not->toContain('unsplash');
    }
});

test('synthwave poster contains pixel art and crt layers', function () {
    $contents = file_get_contents(resource_path('js/components/SynthwavePoster.svelte'));

    expect($contents)->toContain('pixel-tower')
        ->and($contents)->toContain('pixel-noise')
        ->and($contents)->toContain('crt-lines');
});

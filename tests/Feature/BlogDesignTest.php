<?php

use Inertia\Testing\AssertableInertia;

test('blog index is the public start page', function () {
    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Blog/Index')
            ->where('locale', 'en')
            ->where('meta.title', 'Sovereign Manual Magazine'));
});

test('german blog index is the localized public start page', function () {
    $this->get(route('blog.de.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Blog/Index')
            ->where('locale', 'de')
            ->where('copy.read', 'Artikel lesen'));
});

test('legacy blog index urls redirect to the magazine start page', function () {
    $this->get('/blog')->assertRedirect(route('blog.index'));
    $this->get('/de/blog')->assertRedirect(route('blog.de.index'));
});

test('public pages are not wrapped in the starterkit app layout', function () {
    $contents = file_get_contents(resource_path('js/app.ts'));

    expect($contents)->toContain("case name.startsWith('Blog/'):")
        ->and($contents)->not->toContain("case name === 'Home':")
        ->and($contents)->not->toContain("case name === 'Welcome':");
});

test('public blog frontend does not link login admin or unsplash', function () {
    $publicFiles = [
        resource_path('js/components/PublicNav.svelte'),
        resource_path('js/pages/Blog/Index.svelte'),
        resource_path('js/pages/Blog/Show.svelte'),
    ];

    foreach ($publicFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('/login')
            ->and($contents)->not->toContain('Admin')
            ->and($contents)->not->toContain('unsplash');
    }
});

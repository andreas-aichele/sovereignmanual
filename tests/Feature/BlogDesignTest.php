<?php

use Inertia\Testing\AssertableInertia;

test('home page renders the synthwave brand entry point', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('meta.title', 'Sovereign Manual'));
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

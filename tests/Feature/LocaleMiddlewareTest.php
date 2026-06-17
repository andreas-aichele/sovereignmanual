<?php

use App\Models\Post;
use App\Models\PostTranslation;

test('unlocalized magazine routes use the fallback locale for stable seo urls', function () {
    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('lang="en"', false)
        ->assertSee('No published articles yet.');
});

test('stored locale does not change unlocalized magazine routes', function () {
    $this->withCookie('locale', 'de')
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('lang="en"', false)
        ->assertSee('No published articles yet.');
});

test('route locale is preferred over the stored locale', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'English Article',
        'slug' => 'english-article',
    ]);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.localized.show', ['locale' => 'en', 'category' => 'self-custody', 'slug' => 'english-article']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('lang="en"', false)
        ->assertSee('English Article');
});

test('unsupported browser languages fall back to the configured fallback locale', function () {
    config([
        'app.fallback_locale' => 'en',
    ]);

    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('lang="en"', false);
});

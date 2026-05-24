<?php

use App\Models\Post;
use App\Models\PostTranslation;
use Inertia\Testing\AssertableInertia;

test('published posts appear on the blog index', function () {
    $post = Post::factory()->published()->create([
        'topic' => 'Bitcoin self custody basics',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin self custody basics',
        'slug' => 'bitcoin-self-custody-basics',
    ]);

    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Blog/Index')
            ->where('posts.data.0.title', 'Bitcoin self custody basics'));
});

test('unpublished posts are hidden from the public blog', function () {
    $post = Post::factory()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Hidden draft',
        'slug' => 'hidden-draft',
    ]);

    $this->get(route('blog.show', 'hidden-draft'))->assertNotFound();
});

test('localized german posts render through the german route', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Selbstverwahrung',
        'slug' => 'bitcoin-selbstverwahrung',
        'markdown' => '# Bitcoin Selbstverwahrung',
    ]);

    $this->get(route('blog.de.show', 'bitcoin-selbstverwahrung'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Blog/Show')
            ->where('locale', 'de')
            ->where('post.title', 'Bitcoin Selbstverwahrung'));
});

<?php

use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostTranslation;
use Inertia\Testing\AssertableInertia;

test('published posts appear on the magazine index', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'self-custody',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
        'topic' => 'Bitcoin self custody basics',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin self custody basics',
        'slug' => 'bitcoin-self-custody-basics',
    ]);

    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Index')
            ->where('posts.data.0.title', 'Bitcoin self custody basics')
            ->where('posts.data.0.category', 'self-custody')
            ->where('posts.data.0.category_label', 'Self custody')
            ->where('posts.data.0.image_placeholder.category', 'self-custody')
            ->where('posts.data.0.image_placeholder.accent', '#F7931A'));
});

test('newest published post leads the magazine index', function () {
    $olderPost = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
    ]);
    $newerPost = Post::factory()->published()->create([
        'published_at' => now()->subHour(),
    ]);

    PostTranslation::factory()->create([
        'post_id' => $olderPost->id,
        'locale' => 'en',
        'title' => 'Older article',
        'slug' => 'older-article',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $newerPost->id,
        'locale' => 'en',
        'title' => 'Newest article',
        'slug' => 'newest-article',
    ]);

    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Index')
            ->where('posts.data.0.title', 'Newest article')
            ->where('posts.data.1.title', 'Older article'));
});

test('unpublished posts are hidden from the public magazine', function () {
    $post = Post::factory()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Hidden draft',
        'slug' => 'hidden-draft',
    ]);

    $this->get(route('magazine.show', 'hidden-draft'))->assertNotFound();
});

test('localized german posts render through the german route', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'bitcoin',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Selbstverwahrung',
        'slug' => 'bitcoin-selbstverwahrung',
        'markdown' => '# Bitcoin Selbstverwahrung',
    ]);

    $this->get(route('magazine.de.show', 'bitcoin-selbstverwahrung'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Show')
            ->where('locale', 'de')
            ->where('post.title', 'Bitcoin Selbstverwahrung')
            ->where('post.image_placeholder.category', 'bitcoin')
            ->where('copy.back', 'Zurück zum Magazin'));
});

test('german category labels use correct umlauts', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'financial-independence',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Finanzielle Unabhängigkeit',
        'slug' => 'finanzielle-unabhaengigkeit',
    ]);

    $this->get(route('magazine.de.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Index')
            ->where('posts.data.0.category_label', 'Finanzielle Unabhängigkeit'));
});

test('markdown is rendered to sanitized html for articles', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Markdown Rendering',
        'slug' => 'markdown-rendering',
        'markdown' => "# Markdown Rendering\n\nA **strong** point.\n\n- first\n- second\n\n<script>alert('x')</script>",
    ]);

    $this->get(route('magazine.show', 'markdown-rendering'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Magazine/Show')
            ->where('post.html', fn (string $html): bool => str_contains($html, '<h1>Markdown Rendering</h1>')
                && str_contains($html, '<strong>strong</strong>')
                && str_contains($html, '<li>first</li>')
                && ! str_contains($html, '<script>')));
});

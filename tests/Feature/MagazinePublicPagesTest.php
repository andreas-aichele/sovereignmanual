<?php

use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostTranslation;

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
        ->assertViewIs('magazine.index')
        ->assertSee('Bitcoin self custody basics')
        ->assertSee('Self custody')
        ->assertSee('#F7931A', false);
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
        ->assertViewIs('magazine.index')
        ->assertSeeInOrder(['Newest article', 'Older article']);
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
        ->assertViewIs('magazine.show')
        ->assertViewHas('locale', 'de')
        ->assertSee('Bitcoin Selbstverwahrung')
        ->assertSee('bitcoin')
        ->assertSee('Zurück zum Magazin');
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
        ->assertViewIs('magazine.index')
        ->assertSee('Finanzielle Unabhängigkeit');
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
        ->assertViewIs('magazine.show')
        ->assertSee('<h1>Markdown Rendering</h1>', false)
        ->assertSee('<strong>strong</strong>', false)
        ->assertSee('<li>first</li>', false)
        ->assertDontSee('<script>', false);
});

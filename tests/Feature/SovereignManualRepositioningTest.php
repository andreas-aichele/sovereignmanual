<?php

use App\Enums\ContentType;
use App\Enums\Language;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\CategorySeeder;

test('the start page introduces all three paths and the double opt-in waitlist', function () {
    $this->seed(CategorySeeder::class);

    $category = Category::query()
        ->where('key', 'self-custody')
        ->where('lang', Language::English)
        ->firstOrFail();
    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
        'content_type' => ContentType::Guide,
    ]);
    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'A practical first step',
        'slug' => 'a-practical-first-step',
    ]);

    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertSee('Where would you like to become more independent?')
        ->assertSee('Bitcoin &amp; Money', false)
        ->assertSee('Digital Sovereignty')
        ->assertSee('Decisions &amp; Preparedness', false)
        ->assertSee('A practical first step')
        ->assertSee('Join the waitlist')
        ->assertSee('action="'.route('waitlist.store').'"', false)
        ->assertSee('name="consent"', false);
});

test('briefings disclose their method, sources, and correction route', function () {
    $this->seed(CategorySeeder::class);

    $category = Category::query()
        ->where('key', 'news')
        ->where('lang', Language::English)
        ->firstOrFail();
    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
        'content_type' => ContentType::Briefing,
        'ai_metadata' => [
            'auto_generated' => true,
            'method' => 'automated_research_and_publishing_workflow',
        ],
        'sources' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
                'publisher' => 'Bitcoin Core',
                'published_at' => '2026-07-17',
                'type' => 'technical',
            ],
            [
                'title' => 'Unsafe source',
                'url' => 'javascript:alert(1)',
            ],
        ],
    ]);
    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Core briefing',
        'slug' => 'bitcoin-core-briefing',
    ]);

    $this->get(route('magazine.show', [
        'category' => 'news',
        'slug' => 'bitcoin-core-briefing',
    ]))
        ->assertSuccessful()
        ->assertSee('Briefing')
        ->assertSee('Automated research and publishing workflow')
        ->assertSee('Bitcoin Core release notes')
        ->assertSee('https://bitcoincore.org/en/releases/example/', false)
        ->assertDontSee('Unsafe source')
        ->assertSee('Report an issue or correction')
        ->assertSee('https://github.com/andreas-aichele/sovereignmanual/issues/new', false);
});

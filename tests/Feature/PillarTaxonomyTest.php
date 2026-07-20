<?php

use App\Enums\ContentType;
use App\Enums\Language;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Pillar;
use App\Models\Post;
use Database\Seeders\CategorySeeder;

test('category seeding maps every localized category to one of three localized pillars', function () {
    $this->seed(CategorySeeder::class);

    expect(Pillar::query()->distinct()->orderBy('key')->pluck('key')->all())->toBe([
        'bitcoin-money',
        'decisions-preparedness',
        'digital-sovereignty',
    ]);

    $pillarKeys = [
        'self-custody' => 'bitcoin-money',
        'financial-sovereignty' => 'bitcoin-money',
        'economics' => 'bitcoin-money',
        'news' => 'bitcoin-money',
        'privacy-security' => 'digital-sovereignty',
        'mindset' => 'decisions-preparedness',
        'family-legacy' => 'decisions-preparedness',
    ];

    foreach ($pillarKeys as $categoryKey => $pillarKey) {
        $englishCategory = Category::query()
            ->with('pillar')
            ->where('key', $categoryKey)
            ->where('lang', Language::English)
            ->firstOrFail();
        $germanCategory = Category::query()
            ->with('pillar')
            ->where('key', $categoryKey)
            ->where('lang', Language::German)
            ->firstOrFail();

        expect($englishCategory->pillar?->key)->toBe($pillarKey)
            ->and($englishCategory->pillar?->lang)->toBe(Language::English)
            ->and($germanCategory->pillar?->key)->toBe($pillarKey)
            ->and($germanCategory->pillar?->lang)->toBe(Language::German);
    }

    $bitcoinMoney = Pillar::query()
        ->where('key', 'bitcoin-money')
        ->where('lang', Language::English)
        ->firstOrFail();

    expect($bitcoinMoney->localizedSlug('de'))->toBe('bitcoin-geld')
        ->and($bitcoinMoney->localizedDescription('de'))->toContain('Bitcoin')
        ->and($bitcoinMoney->matchesSlug('bitcoin-geld', 'de'))->toBeTrue()
        ->and($bitcoinMoney->categories()->count())->toBe(4);
});

test('pillar backfill creates localized pillars and maps existing categories without seeding', function () {
    $englishCategory = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'slug' => 'privacy-security',
        'pillar_id' => null,
    ]);
    $germanCategory = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'slug' => 'privatsphaere-sicherheit',
        'pillar_id' => null,
    ]);

    $migration = require database_path('migrations/2026_07_17_163138_backfill_pillars_and_category_assignments.php');

    $migration->up();

    expect(Pillar::query()->where('key', 'digital-sovereignty')->count())->toBe(2)
        ->and($englishCategory->refresh()->pillar?->key)->toBe('digital-sovereignty')
        ->and($englishCategory->pillar?->lang)->toBe(Language::English)
        ->and($germanCategory->refresh()->pillar?->key)->toBe('digital-sovereignty')
        ->and($germanCategory->pillar?->lang)->toBe(Language::German);
});

test('content types and structured sources are stored with model casts', function () {
    $topic = ContentTopic::factory()->create([
        'content_type' => ContentType::Checklist,
    ]);
    $sources = [
        [
            'title' => 'Bitcoin Core release notes',
            'url' => 'https://bitcoincore.org/en/releases/example/',
            'publisher' => 'Bitcoin Core',
            'published_at' => '2026-07-17',
            'source_type' => 'technical',
            'credibility_note' => 'Official technical release notes.',
        ],
    ];
    $post = Post::factory()->create([
        'content_topic_id' => $topic->id,
        'content_type' => ContentType::Analysis,
        'sources' => $sources,
    ]);

    expect($topic->refresh()->content_type)->toBe(ContentType::Checklist)
        ->and($post->refresh()->content_type)->toBe(ContentType::Analysis)
        ->and($post->sources)->toBe($sources);
});

test('news backfill turns historic news into briefings and retains their verified sources', function () {
    $newsCategory = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $sources = [
        [
            'title' => 'Bitcoin Core release notes',
            'url' => 'https://bitcoincore.org/en/releases/example/',
            'publisher' => 'Bitcoin Core',
            'source_type' => 'technical',
            'credibility_note' => 'Official technical release notes.',
        ],
    ];
    $topic = ContentTopic::factory()->create([
        'category_id' => $newsCategory->id,
        'content_type' => ContentType::Guide,
        'constraints' => [
            'news_research' => [
                'sources' => $sources,
            ],
        ],
    ]);
    $post = Post::factory()->create([
        'content_topic_id' => $topic->id,
        'category_id' => $newsCategory->id,
        'content_type' => ContentType::Guide,
        'sources' => null,
    ]);

    $migration = require database_path('migrations/2026_07_17_155611_backfill_news_content_types_and_post_sources.php');

    $migration->up();

    expect($topic->refresh()->content_type)->toBe(ContentType::Briefing)
        ->and($post->refresh()->content_type)->toBe(ContentType::Briefing)
        ->and($post->sources)->toBe($sources);
});

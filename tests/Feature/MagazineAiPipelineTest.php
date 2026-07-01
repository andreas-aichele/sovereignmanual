<?php

use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\Language;
use App\Enums\PostStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Services\MagazineAiPipeline;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Psr\Log\LoggerInterface;

test('pipeline creates a published post with english and german translations', function () {
    config(['ai.providers.gemini.key' => null]);
    $createdStatus = null;
    $createdPublishedAt = null;

    Post::created(function (Post $post) use (&$createdStatus, &$createdPublishedAt): void {
        $createdStatus = $post->status;
        $createdPublishedAt = $post->published_at;
    });

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Why Bitcoin custody matters',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();
    $asset = $post->assets()->firstOrFail();
    $englishBlockTypes = $post->blocks()->where('locale', 'en')->pluck('type')->all();
    $englishSection = $post->blocks()->where('locale', 'en')->where('type', 'section')->firstOrFail();

    expect($post->status)->toBe(PostStatus::Published)
        ->and($createdStatus)->toBe(PostStatus::Draft)
        ->and($createdPublishedAt)->toBeNull()
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and(mb_strlen($englishTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($englishTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($englishTranslation->seo['keywords'])->toContain('bitcoin')
        ->and($englishTranslation->slug)->toBe('why-bitcoin-custody-matters')
        ->and($germanTranslation->title)->toBe('Why Bitcoin custody matters')
        ->and($germanTranslation->slug)->toBe('why-bitcoin-custody-matters-2')
        ->and(mb_strlen($germanTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($germanTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($germanTranslation->seo['keywords'])->toContain('bitcoin')
        ->and($post->category?->key)->toBe('self-custody')
        ->and($post->blocks()->where('locale', 'en')->count())->toBeGreaterThan(1)
        ->and($post->blocks()->where('locale', 'de')->count())->toBeGreaterThan(1)
        ->and($englishBlockTypes)->toContain('section')
        ->and($englishSection->heading)->toBe('Why Bitcoin custody matters')
        ->and($englishSection->anchor)->toBe('why-bitcoin-custody-matters')
        ->and($englishBlockTypes)->toContain('flow_diagram')
        ->and($asset->url)->toBeNull()
        ->and($asset->metadata['role'])->toBe('header')
        ->and($asset->metadata['reason'])->toBe('image_generation_not_configured')
        ->and($asset->metadata['prompt_version'])->toBe(2)
        ->and($asset->alt_text)->toBe('Header image for the article Why Bitcoin custody matters.')
        ->and($asset->alt_text)->not->toContain('Synthwave')
        ->and($asset->metadata['alt_texts']['en'])->toBe('Header image for the article Why Bitcoin custody matters.')
        ->and($asset->metadata['alt_texts']['de'])->toBe('Header image for the article Why Bitcoin custody matters.')
        ->and($asset->prompt)->toContain('Full-bleed synthwave editorial website background')
        ->and($asset->prompt)->toContain('no border, no frame, no book')
        ->and($asset->prompt)->toContain('human-scale personal scene details')
        ->and($asset->prompt)->toContain('no glossy AI-slop aesthetic')
        ->and($asset->prompt)->toContain('no stock-photo look')
        ->and($asset->prompt)->not->toContain('unsplash')
        ->and($post->aiRuns()->count())->toBeGreaterThanOrEqual(1)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Published);
});

test('pipeline creates translations for languages defined in the language enum', function () {
    config([
        'ai.providers.gemini.key' => null,
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Why Bitcoin custody matters',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);

    expect($post->translations()->pluck('locale')->sort()->values()->all())->toBe(['de', 'en'])
        ->and($post->blocks()->pluck('locale')->unique()->sort()->values()->all())->toBe(['de', 'en'])
        ->and($post->aiRuns()->where('type', AiRunType::Translation)->exists())->toBeTrue();
});

test('pipeline no longer appends related reading blocks to new articles', function () {
    config(['ai.providers.gemini.key' => null]);

    $existingPost = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $existingPost->id,
        'locale' => 'en',
        'title' => 'Bitcoin wallet backups',
        'slug' => 'bitcoin-wallet-backups',
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();

    expect($englishTranslation->markdown)
        ->not->toContain('Related reading')
        ->not->toContain('[Bitcoin wallet backups]')
        ->and($englishTranslation->seo['internal_links'][0]['slug'])->toBe('bitcoin-wallet-backups');
    expect($post->blocks()->where('heading', 'Related reading')->exists())->toBeFalse();
});

test('pipeline keeps h1 title fallback within limits without cutting words', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners who want practical security without relying on custodians',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();

    expect(mb_strlen($englishTranslation->title))->toBeLessThanOrEqual(70)
        ->and($englishTranslation->markdown)->toContain('## Bitcoin self custody threat models')
        ->and($englishTranslation->title)->toEndWith('practical')
        ->and($englishTranslation->title)->not->toEndWith('practic');
});

test('pipeline retries seo title generation until length requirements pass', function () {
    $pipeline = file_get_contents(app_path('Services/MagazineAiPipeline.php'));

    expect($pipeline)->toContain('Generate the visible H1 article_title and the browser/search meta_title directly at the correct length')
        ->and($pipeline)->toContain('Do not return an overlong title for PHP to shorten later')
        ->and($pipeline)->toContain('previous_attempt_feedback')
        ->and($pipeline)->toContain('$problems = []')
        ->and($pipeline)->toContain('for ($attempt = 1; $attempt <= 3; $attempt++)');
});

test('pipeline fallback translations are created for supported locales without locale-specific copy', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();
    $germanInsight = $post->blocks()->where('locale', 'de')->where('type', 'insight')->firstOrFail();

    expect($germanTranslation->title)->toBe('Bitcoin self custody threat models for beginners')
        ->and($germanInsight->data['title'])->toBe('Core insight')
        ->and($germanTranslation->markdown)->toContain('Bitcoin self custody threat models for beginners');
});

test('pipeline block planning preserves article detail up to twelve blocks', function () {
    $pipeline = file_get_contents(app_path('Services/MagazineAiPipeline.php'));

    expect($pipeline)->toContain('Preserve the full article detail')
        ->and($pipeline)->toContain('Do not summarize, shorten, or omit practical examples')
        ->and($pipeline)->toContain('Split the full draft into section blocks with several paragraphs each')
        ->and($pipeline)->toContain('->take(12)');
});

test('topic ideation creates scheduled topics', function () {
    config(['ai.providers.gemini.key' => null]);

    $this->artisan('app:ideate-magazine-topics --count=2')->assertSuccessful();

    expect(ContentTopic::query()->count())->toBe(2)
        ->and(ContentTopic::query()->where('status', ContentTopicStatus::Scheduled)->count())->toBe(2);
});

test('evergreen topic ideation chooses a non news category before creating topics', function () {
    config(['ai.providers.gemini.key' => null]);

    $privacy = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'slug' => 'privacy-security',
        'name' => 'Privacy & Security',
    ]);
    Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    Category::query()->whereNotIn('id', [$privacy->id])->where('key', '!=', 'news')->delete();

    $this->artisan('app:ideate-magazine-topics --count=1')->assertSuccessful();

    $topic = ContentTopic::query()
        ->where('category_id', $privacy->id)
        ->latest('id')
        ->firstOrFail();

    expect($topic->category_id)->toBe($privacy->id)
        ->and($topic->category?->key)->toBe('privacy-security')
        ->and($topic->category?->key)->not->toBe('news');
});

test('topic ideation stores existing category topics as similarity exclusions', function () {
    config(['ai.providers.gemini.key' => null]);

    $category = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'slug' => 'privacy-security',
        'name' => 'Privacy & Security',
    ]);
    Category::query()->whereKeyNot($category->id)->delete();

    Post::factory()->published()->create([
        'category_id' => $category->id,
        'topic' => 'Bitcoin privacy threat models everyone keeps repeating',
    ]);
    Category::query()->whereKeyNot($category->id)->delete();

    $this->artisan('app:ideate-magazine-topics --count=1')->assertSuccessful();

    $topic = ContentTopic::query()
        ->where('category_id', $category->id)
        ->latest('id')
        ->firstOrFail();

    expect($topic->constraints['avoid_similar_topics'])->toContain('Bitcoin privacy threat models everyone keeps repeating');
});

test('news ideation without provider web research creates no topics', function () {
    config(['ai.providers.gemini.key' => null]);

    $this->artisan('app:ideate-news-topics --count=1')->assertFailed();

    expect(ContentTopic::query()->count())->toBe(0);
});

test('news research must include at least two credible independent sources', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
        'example.com/*' => Http::response('', 200),
    ]);

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch');

    $topics = $method->invoke($pipeline, [
        'grounding_citations' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
            ],
            [
                'title' => 'GitHub release',
                'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Two independent credible sources confirm the update.'],
                'open_questions' => ['Deployment timing varies by user.'],
            ],
            [
                'title' => 'Unverified Bitcoin rumor',
                'summary' => 'A rumor with weak sourcing.',
                'sources' => [
                    [
                        'title' => 'Anonymous post',
                        'url' => 'https://example.com/rumor',
                        'type' => 'supporting',
                    ],
                ],
            ],
        ],
    ], $category, 2, []);

    expect($topics)->toHaveCount(1);

    $topic = $topics->first();

    expect($topic->category?->key)->toBe('news')
        ->and($topic->constraints['news_research']['sources'])->toHaveCount(2)
        ->and($topic->constraints['news_research']['grounding_citations'])->toHaveCount(2)
        ->and($topic->constraints['news_research']['credibility_notes'])->toContain('Two independent credible sources confirm the update.');
});

test('news topics without verified sources are not generated into posts', function () {
    config(['ai.providers.gemini.key' => null]);
    Http::fake();

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $topic = ContentTopic::factory()->due()->create([
        'category_id' => $category->id,
        'title' => 'Unverified Bitcoin news item',
        'constraints' => ['tone' => 'clear, sourced, non-hype'],
    ]);

    expect(fn () => app(MagazineAiPipeline::class)->generatePost($topic))
        ->toThrow(RuntimeException::class, 'News topics require at least two credible independent sources');

    expect(Post::query()->count())->toBe(0)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Archived);
});

test('generation job skips invalid news topics after archiving them', function () {
    Http::fake();

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $topic = ContentTopic::factory()->due()->create([
        'category_id' => $category->id,
        'title' => 'Old news topic with dead sources',
        'constraints' => ['news_research' => ['sources' => []]],
    ]);

    (new GeneratePostFromTopic($topic))->handle(app(MagazineAiPipeline::class));

    expect(Post::query()->count())->toBe(0)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Archived);
});

test('news research rejects unreachable source urls', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 404),
    ]);

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch');

    $topics = $method->invoke($pipeline, [
        'grounding_citations' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
            ],
            [
                'title' => 'Missing GitHub release',
                'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/missing',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'Missing GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/missing',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['One source is unavailable.'],
                'open_questions' => [],
            ],
        ],
    ], $category, 1, []);

    expect($topics)->toHaveCount(0);
});

test('news research accepts verified direct source urls even without google grounding citations', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch');

    $topics = $method->invoke($pipeline, [
        'grounding_citations' => [],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Sources are verified directly by URL.'],
                'open_questions' => [],
            ],
        ],
    ], $category, 1, []);

    expect($topics)->toHaveCount(1)
        ->and($topics->first()->constraints['news_research']['grounding_citations'])->toBe([]);
});

test('news research requires at least one strong primary technical or official source', function () {
    Http::fake([
        'example-news.com/*' => Http::response('', 200),
        'another-news.com/*' => Http::response('', 200),
    ]);

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch');

    $topics = $method->invoke($pipeline, [
        'grounding_citations' => [],
        'topics' => [
            [
                'title' => 'Bitcoin policy story with only secondary reporting',
                'summary' => 'A story sourced only by secondary reporting.',
                'sources' => [
                    [
                        'title' => 'First report',
                        'url' => 'https://example-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Example News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Known publication.',
                    ],
                    [
                        'title' => 'Second report',
                        'url' => 'https://another-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Another News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Independent publication.',
                    ],
                ],
                'credibility_notes' => ['No primary, technical, or official source is available.'],
                'open_questions' => [],
            ],
        ],
    ], $category, 1, []);

    expect($topics)->toHaveCount(0);
});

test('news research stores google redirect citations as diagnostics', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $category = Category::factory()->create([
        'key' => 'news',
        'lang' => Language::English,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch');

    $topics = $method->invoke($pipeline, [
        'grounding_citations' => [
            [
                'title' => 'bitcoincore.org',
                'url' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/bitcoin-core-release',
            ],
            [
                'title' => 'github.com',
                'url' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/github-release',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core publishes a sourced update',
                'summary' => 'A sourced update about Bitcoin Core.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Grounded by Google Search redirect citations.'],
                'open_questions' => [],
            ],
        ],
    ], $category, 1, []);

    expect($topics)->toHaveCount(1)
        ->and($topics->first()->constraints['news_research']['sources'])->toHaveCount(2);
});

test('console schedule includes the weekly news publishing slot', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));

    expect($schedule)
        ->toContain("Schedule::command('app:ideate-news-topics --count=1')")
        ->toContain("->weeklyOn(3, '08:00')")
        ->toContain("->weeklyOn(3, '08:10')");
});

test('generation command queues due topics', function () {
    Queue::fake();

    $topic = ContentTopic::factory()->due()->create();

    $this->artisan('app:generate-due-magazine-posts')->assertSuccessful();

    Queue::assertPushed(GeneratePostFromTopic::class, fn (GeneratePostFromTopic $job): bool => $job->topic->is($topic));
});

test('queue logs are written to a dedicated daily log file', function () {
    expect(config('logging.channels.queue.driver'))->toBe('daily')
        ->and(config('logging.channels.queue.path'))->toBe(storage_path('logs/queue.log'));
});

test('generation job failure writes useful queue log context', function () {
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Queue diagnostics topic',
    ]);
    $logger = Mockery::mock(LoggerInterface::class);

    Log::shouldReceive('channel')
        ->once()
        ->with('queue')
        ->andReturn($logger);

    $logger->shouldReceive('error')
        ->once()
        ->with('Magazine post generation job failed.', Mockery::on(
            fn (array $context): bool => $context['content_topic_id'] === $topic->id
                && $context['content_topic_title'] === 'Queue diagnostics topic'
                && $context['max_tries'] === 3
                && $context['timeout'] === 1200
                && $context['exception_class'] === RuntimeException::class
                && $context['exception_message'] === 'Queue worker stopped unexpectedly'
                && array_key_exists('memory_peak_mb', $context)
        ));

    (new GeneratePostFromTopic($topic))->failed(new RuntimeException('Queue worker stopped unexpectedly'));
});

test('generation job discards retries when the topic was deleted', function () {
    $attributes = (new ReflectionClass(GeneratePostFromTopic::class))
        ->getAttributes(DeleteWhenMissingModels::class);

    expect($attributes)->not->toBeEmpty();
});

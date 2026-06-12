<?php

use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Services\MagazineAiPipeline;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Psr\Log\LoggerInterface;

test('pipeline creates a published post with english and german translations', function () {
    config(['ai.providers.gemini.key' => null]);

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
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and(mb_strlen($englishTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($englishTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($englishTranslation->seo['keywords'])->toContain('bitcoin')
        ->and($englishTranslation->slug)->toBe('why-bitcoin-custody-matters')
        ->and($germanTranslation->title)->toBe('Warum Bitcoin-Verwahrung wichtig ist')
        ->and($germanTranslation->slug)->toBe('warum-bitcoin-verwahrung-wichtig-ist')
        ->and(mb_strlen($germanTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($germanTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($germanTranslation->seo['keywords'])->toContain('bitcoin')
        ->and($post->blocks()->where('locale', 'en')->count())->toBeGreaterThan(1)
        ->and($post->blocks()->where('locale', 'de')->count())->toBeGreaterThan(1)
        ->and($englishBlockTypes)->toContain('section')
        ->and($englishSection->heading)->toBe('Why Bitcoin custody matters')
        ->and($englishSection->anchor)->toBe('why-bitcoin-custody-matters')
        ->and($englishBlockTypes)->toContain('flow_diagram')
        ->and($asset->url)->toBeNull()
        ->and($asset->metadata['role'])->toBe('header')
        ->and($asset->metadata['reason'])->toBe('image_generation_not_configured')
        ->and($asset->prompt)->toContain('Premium synthwave editorial header image')
        ->and($asset->prompt)->toContain('no stock-photo look')
        ->and($asset->prompt)->not->toContain('unsplash')
        ->and($post->aiRuns()->count())->toBeGreaterThanOrEqual(1)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Published);
});

test('pipeline adds relevant internal links when published articles exist', function () {
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
        ->toContain('Related reading')
        ->toContain('[Bitcoin wallet backups]')
        ->and($englishTranslation->seo['internal_links'][0]['slug'])->toBe('bitcoin-wallet-backups');
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

test('pipeline keeps internal link blocks within the twelve block limit', function () {
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'withInternalLinks');
    $blocks = collect(range(1, 12))
        ->map(fn (int $index): array => [
            'type' => 'section',
            'heading' => "Section {$index}",
            'anchor' => "section-{$index}",
            'markdown' => "Text {$index}",
            'data' => [],
        ])
        ->all();

    $result = $method->invoke($pipeline, $blocks, [
        ['title' => 'Bitcoin wallet backups', 'url' => '/magazine/bitcoin-wallet-backups', 'slug' => 'bitcoin-wallet-backups'],
    ], 'en');

    expect($result)->toHaveCount(12)
        ->and($result[11]['heading'])->toBe('Related reading');
});

test('pipeline fallback german titles use correct umlauts', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();
    $germanInsight = $post->blocks()->where('locale', 'de')->where('type', 'insight')->firstOrFail();

    expect($germanTranslation->title)->toBe('Bitcoin-Selbstverwahrung: Bedrohungsmodelle für Einsteiger')
        ->and($germanInsight->data['title'])->toBe('Kernaussage')
        ->and($germanTranslation->markdown)->not->toContain('Souveraene')
        ->and($germanTranslation->markdown)->not->toContain('Unabhaengigkeit');
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

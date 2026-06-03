<?php

use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\ContentTopic;
use App\Services\MagazineAiPipeline;
use Illuminate\Support\Facades\Queue;

test('pipeline creates a published post with english and german translations', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Why Bitcoin custody matters',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();
    $asset = $post->assets()->firstOrFail();

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and($germanTranslation->title)->toBe('Warum Bitcoin-Verwahrung wichtig ist')
        ->and($germanTranslation->slug)->toBe('warum-bitcoin-verwahrung-wichtig-ist')
        ->and($asset->url)->toBeNull()
        ->and($asset->prompt)->toContain('Synthwave cypherpunk')
        ->and($asset->prompt)->toContain('no stock-photo look')
        ->and($asset->prompt)->not->toContain('unsplash')
        ->and($post->aiRuns()->count())->toBeGreaterThanOrEqual(1)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Published);
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

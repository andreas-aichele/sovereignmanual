<?php

use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Services\BlogAiPipeline;
use Illuminate\Support\Facades\Queue;

test('pipeline creates a published post with english and german translations', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Why Bitcoin custody matters',
    ]);

    $post = app(BlogAiPipeline::class)->generatePost($topic);

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and($post->aiRuns()->count())->toBeGreaterThanOrEqual(1)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Published);
});

test('generation command queues due topics', function () {
    Queue::fake();

    $topic = ContentTopic::factory()->due()->create();

    $this->artisan('app:generate-due-blog-posts')->assertSuccessful();

    Queue::assertPushed(GeneratePostFromTopic::class, fn (GeneratePostFromTopic $job): bool => $job->topic->is($topic));
});

test('freshness review updates the next review date', function () {
    $post = Post::factory()->published()->create([
        'next_review_at' => now()->subDay(),
    ]);

    app(BlogAiPipeline::class)->refreshPost($post);

    expect($post->refresh()->next_review_at->isFuture())->toBeTrue();
});

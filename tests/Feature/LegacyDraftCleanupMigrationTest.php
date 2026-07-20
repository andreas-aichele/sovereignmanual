<?php

use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Models\AiRun;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostBlock;

test('the incomplete archived wallet backup draft and its ai runs are removed', function () {
    $archivedTopic = ContentTopic::factory()->create([
        'slug' => 'wallet-backup-recovery-drill-live-20260707172459',
        'status' => ContentTopicStatus::Archived,
    ]);
    $incompletePost = Post::factory()->create([
        'content_topic_id' => $archivedTopic->id,
        'status' => PostStatus::Archived,
    ]);
    AiRun::factory()->create([
        'post_id' => $incompletePost->id,
        'content_topic_id' => $archivedTopic->id,
    ]);

    $migration = require database_path('migrations/2026_07_20_092743_remove_incomplete_archived_wallet_backup_draft.php');

    $migration->up();

    expect(Post::query()->find($incompletePost->id))->toBeNull()
        ->and(ContentTopic::query()->find($archivedTopic->id))->toBeNull()
        ->and(AiRun::query()
            ->where('post_id', $incompletePost->id)
            ->exists())->toBeFalse();
});

test('the cleanup keeps complete posts and their ai runs within the same legacy topic', function () {
    $archivedTopic = ContentTopic::factory()->create([
        'slug' => 'wallet-backup-recovery-drill-live-20260707172459',
        'status' => ContentTopicStatus::Archived,
    ]);
    $incompletePost = Post::factory()->create([
        'content_topic_id' => $archivedTopic->id,
        'status' => PostStatus::Archived,
    ]);
    AiRun::factory()->create([
        'post_id' => $incompletePost->id,
        'content_topic_id' => $archivedTopic->id,
    ]);
    $completePost = Post::factory()->create([
        'content_topic_id' => $archivedTopic->id,
        'status' => PostStatus::Archived,
    ]);
    PostBlock::factory()->create(['post_id' => $completePost->id]);
    $completeAiRun = AiRun::factory()->create([
        'post_id' => $completePost->id,
        'content_topic_id' => $archivedTopic->id,
    ]);

    $migration = require database_path('migrations/2026_07_20_092743_remove_incomplete_archived_wallet_backup_draft.php');

    $migration->up();

    expect(Post::query()->find($incompletePost->id))->toBeNull()
        ->and(ContentTopic::query()->find($archivedTopic->id))->not->toBeNull()
        ->and(AiRun::query()
            ->where('post_id', $incompletePost->id)
            ->exists())->toBeFalse()
        ->and(Post::query()->find($completePost->id))->not->toBeNull()
        ->and(AiRun::query()->find($completeAiRun->id))->not->toBeNull();
});

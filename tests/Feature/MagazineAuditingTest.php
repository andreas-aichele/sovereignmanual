<?php

use App\Models\ContentTopic;
use App\Models\Post;
use OwenIt\Auditing\Models\Audit;

test('magazine models record audit entries', function () {
    config(['audit.enabled' => true]);
    config(['audit.console' => true]);

    $topic = ContentTopic::factory()->create();
    $post = Post::factory()->create([
        'content_topic_id' => $topic->id,
    ]);

    $post->update([
        'audience_level' => 'intermediate',
    ]);

    expect(Audit::query()->where('auditable_type', Post::class)->count())->toBeGreaterThanOrEqual(2)
        ->and(Audit::query()->where('auditable_type', ContentTopic::class)->exists())->toBeTrue();
});

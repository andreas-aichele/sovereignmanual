<?php

use App\Enums\PostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('content_topics')
            || ! Schema::hasTable('posts')
            || ! Schema::hasTable('post_blocks')
            || ! Schema::hasTable('ai_runs')) {
            return;
        }

        $topicIds = DB::table('content_topics')
            ->where('slug', 'wallet-backup-recovery-drill-live-20260707172459')
            ->where('status', 'archived')
            ->pluck('id');

        if ($topicIds->isEmpty()) {
            return;
        }

        $postIds = DB::table('posts')
            ->leftJoin('post_blocks', 'post_blocks.post_id', '=', 'posts.id')
            ->whereIn('posts.content_topic_id', $topicIds)
            ->where('posts.status', PostStatus::Archived->value)
            ->groupBy('posts.id')
            ->havingRaw('COUNT(post_blocks.id) = 0')
            ->pluck('posts.id');

        if ($postIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($postIds, $topicIds): void {
            DB::table('ai_runs')->whereIn('post_id', $postIds)->delete();
            DB::table('posts')->whereIn('id', $postIds)->delete();

            $orphanedTopicIds = DB::table('content_topics')
                ->whereIn('id', $topicIds)
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('posts')
                        ->whereColumn('posts.content_topic_id', 'content_topics.id');
                })
                ->pluck('id');

            DB::table('ai_runs')->whereIn('content_topic_id', $orphanedTopicIds)->delete();
            DB::table('content_topics')->whereIn('id', $orphanedTopicIds)->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The removed incomplete draft cannot be reconstructed safely.
    }
};

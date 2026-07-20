<?php

use App\Enums\ContentType;
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
        if (! Schema::hasTable('categories')
            || ! Schema::hasTable('content_topics')
            || ! Schema::hasTable('posts')) {
            return;
        }

        DB::table('content_topics')
            ->whereNull('content_type')
            ->update(['content_type' => ContentType::Guide->value]);

        DB::table('posts')
            ->whereNull('content_type')
            ->update(['content_type' => ContentType::Guide->value]);

        DB::table('content_topics')
            ->join('categories', 'categories.id', '=', 'content_topics.category_id')
            ->select('content_topics.id')
            ->where('categories.key', 'news')
            ->orderBy('content_topics.id')
            ->lazyById(100, 'content_topics.id', 'id')
            ->each(function (object $topic): void {
                DB::table('content_topics')
                    ->where('id', $topic->id)
                    ->update(['content_type' => ContentType::Briefing->value]);
            });

        DB::table('posts')
            ->leftJoin('categories as post_categories', 'post_categories.id', '=', 'posts.category_id')
            ->leftJoin('content_topics', 'content_topics.id', '=', 'posts.content_topic_id')
            ->leftJoin('categories as topic_categories', 'topic_categories.id', '=', 'content_topics.category_id')
            ->select([
                'posts.id',
                'posts.sources',
                'post_categories.key as post_category_key',
                'topic_categories.key as topic_category_key',
                'content_topics.constraints as topic_constraints',
            ])
            ->orderBy('posts.id')
            ->lazyById(100, 'posts.id', 'id')
            ->each(function (object $post): void {
                $updates = [];

                if ($post->post_category_key === 'news' || $post->topic_category_key === 'news') {
                    $updates['content_type'] = ContentType::Briefing->value;
                }

                $sources = $this->sourcesFromConstraints($post->topic_constraints);

                if ($sources !== [] && in_array($post->sources, [null, '[]'], true)) {
                    $encodedSources = json_encode($sources, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    if ($encodedSources !== false) {
                        $updates['sources'] = $encodedSources;
                    }
                }

                if ($updates !== []) {
                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update($updates);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing content types and sources may have been edited after the backfill.
    }

    /**
     * @return array<int, mixed>
     */
    private function sourcesFromConstraints(mixed $constraints): array
    {
        if (is_string($constraints)) {
            $constraints = json_decode($constraints, true);
        }

        if (! is_array($constraints)) {
            return [];
        }

        $sources = $constraints['news_research']['sources'] ?? [];

        return is_array($sources) ? array_values($sources) : [];
    }
};

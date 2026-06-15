<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $legacyCategoryMap = [
            'bitcoin' => [
                'slug' => 'self-custody',
                'name' => ['en' => 'Self Custody', 'de' => 'Selbstverwahrung'],
            ],
            'financial-independence' => [
                'slug' => 'financial-sovereignty',
                'name' => ['en' => 'Financial Sovereignty', 'de' => 'Finanzielle Souveränität'],
            ],
            'self-custody' => [
                'slug' => 'self-custody',
                'name' => ['en' => 'Self Custody', 'de' => 'Selbstverwahrung'],
            ],
        ];

        DB::table('content_topics')
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->get()
            ->each(function (object $topic) use ($legacyCategoryMap): void {
                $category = $legacyCategoryMap[$topic->category] ?? [
                    'slug' => $topic->category,
                    'name' => [
                        'en' => str($topic->category)->replace('-', ' ')->title()->toString(),
                        'de' => str($topic->category)->replace('-', ' ')->title()->toString(),
                    ],
                ];

                DB::table('categories')->insertOrIgnore([
                    'slug' => $category['slug'],
                    'name' => json_encode($category['name'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('categories')->insertOrIgnore([
            'slug' => 'self-custody',
            'name' => json_encode(['en' => 'Self Custody', 'de' => 'Selbstverwahrung'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('slug')->constrained()->nullOnDelete();
        });

        $categoryIds = DB::table('categories')->pluck('id', 'slug');

        DB::table('content_topics')
            ->select(['id', 'category'])
            ->orderBy('id')
            ->get()
            ->each(function (object $topic) use ($categoryIds, $legacyCategoryMap): void {
                $categorySlug = $legacyCategoryMap[$topic->category]['slug'] ?? $topic->category;

                DB::table('content_topics')
                    ->where('id', $topic->id)
                    ->update(['category_id' => $categoryIds[$categorySlug] ?? $categoryIds['self-custody']]);
            });

        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('content_topic_id')->constrained()->nullOnDelete();
        });

        DB::table('posts')
            ->select(['posts.id', 'content_topics.category_id'])
            ->join('content_topics', 'content_topics.id', '=', 'posts.content_topic_id')
            ->orderBy('posts.id')
            ->get()
            ->each(function (object $post): void {
                DB::table('posts')
                    ->where('id', $post->id)
                    ->update(['category_id' => $post->category_id]);
            });

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_topics', function (Blueprint $table): void {
            $table->string('category')->default('self-custody')->after('slug');
        });

        $categorySlugs = DB::table('categories')->pluck('slug', 'id');

        DB::table('content_topics')
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $topic) use ($categorySlugs): void {
                DB::table('content_topics')
                    ->where('id', $topic->id)
                    ->update(['category' => $categorySlugs[$topic->category_id] ?? 'self-custody']);
            });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

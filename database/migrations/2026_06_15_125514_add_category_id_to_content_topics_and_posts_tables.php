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
                'key' => 'self-custody',
                'translations' => [
                    'en' => [
                        'slug' => 'self-custody',
                        'name' => 'Self Custody',
                        'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                    ],
                    'de' => [
                        'slug' => 'selbstverwahrung',
                        'name' => 'Selbstverwahrung',
                        'description' => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                    ],
                ],
            ],
            'financial-independence' => [
                'key' => 'financial-sovereignty',
                'translations' => [
                    'en' => [
                        'slug' => 'financial-sovereignty',
                        'name' => 'Financial Sovereignty',
                        'description' => 'Frameworks for saving, spending, and making independent financial decisions in a Bitcoin context.',
                    ],
                    'de' => [
                        'slug' => 'finanzielle-souveraenitaet',
                        'name' => 'Finanzielle Souveränität',
                        'description' => 'Denkmodelle für Sparen, Ausgeben und unabhängige finanzielle Entscheidungen im Bitcoin-Kontext.',
                    ],
                ],
            ],
            'self-custody' => [
                'key' => 'self-custody',
                'translations' => [
                    'en' => [
                        'slug' => 'self-custody',
                        'name' => 'Self Custody',
                        'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                    ],
                    'de' => [
                        'slug' => 'selbstverwahrung',
                        'name' => 'Selbstverwahrung',
                        'description' => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                    ],
                ],
            ],
        ];

        $insertCategory = function (array $category): void {
            collect($category['translations'])->each(function (array $translation, string $lang) use ($category): void {
                DB::table('categories')->insertOrIgnore([
                    'key' => $category['key'],
                    'lang' => $lang,
                    'slug' => $translation['slug'],
                    'name' => $translation['name'],
                    'description' => $translation['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        };

        DB::table('content_topics')
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->get()
            ->each(function (object $topic) use ($insertCategory, $legacyCategoryMap): void {
                $category = $legacyCategoryMap[$topic->category] ?? [
                    'key' => $topic->category,
                    'translations' => [
                        'en' => [
                            'slug' => $topic->category,
                            'name' => str($topic->category)->replace('-', ' ')->title()->toString(),
                            'description' => '',
                        ],
                        'de' => [
                            'slug' => $topic->category,
                            'name' => str($topic->category)->replace('-', ' ')->title()->toString(),
                            'description' => '',
                        ],
                    ],
                ];

                $insertCategory($category);
            });

        $insertCategory([
            'key' => 'self-custody',
            'translations' => [
                'en' => [
                    'slug' => 'self-custody',
                    'name' => 'Self Custody',
                    'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                ],
                'de' => [
                    'slug' => 'selbstverwahrung',
                    'name' => 'Selbstverwahrung',
                    'description' => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                ],
            ],
        ]);

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('slug')->constrained()->nullOnDelete();
        });

        $categoryIds = DB::table('categories')->where('lang', 'en')->pluck('id', 'key');

        DB::table('content_topics')
            ->select(['id', 'category'])
            ->orderBy('id')
            ->get()
            ->each(function (object $topic) use ($categoryIds, $legacyCategoryMap): void {
                $categoryKey = $legacyCategoryMap[$topic->category]['key'] ?? $topic->category;

                DB::table('content_topics')
                    ->where('id', $topic->id)
                    ->update(['category_id' => $categoryIds[$categoryKey] ?? $categoryIds['self-custody']]);
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

        $categoryKeys = DB::table('categories')->pluck('key', 'id');

        DB::table('content_topics')
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $topic) use ($categoryKeys): void {
                DB::table('content_topics')
                    ->where('id', $topic->id)
                    ->update(['category' => $categoryKeys[$topic->category_id] ?? 'self-custody']);
            });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

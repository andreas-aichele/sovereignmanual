<?php

use App\Enums\Language;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostTranslation;

test('legacy posts without a category are assigned to English self custody without changing their URLs', function () {
    $englishSelfCustody = Category::query()->firstOrCreate(
        ['key' => 'self-custody', 'lang' => Language::English],
        [
            'slug' => 'self-custody',
            'name' => 'Self Custody',
            'description' => 'Practical guidance for holding keys, building recovery plans, and reducing custody risk.',
        ],
    );
    $germanSelfCustody = Category::query()->firstOrCreate(
        ['key' => 'self-custody', 'lang' => Language::German],
        [
            'slug' => 'selbstverwahrung',
            'name' => 'Selbstverwahrung',
            'description' => 'Praktische Orientierung für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
        ],
    );
    $otherCategory = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'slug' => 'privacy-security',
    ]);
    $legacyPost = Post::factory()->published()->create([
        'category_id' => null,
    ]);
    $translation = PostTranslation::factory()->create([
        'post_id' => $legacyPost->id,
        'locale' => Language::English->value,
        'slug' => 'legacy-self-custody-url',
    ]);
    $categorizedPost = Post::factory()->create([
        'category_id' => $otherCategory->id,
    ]);

    $migration = require database_path('migrations/2026_07_20_082005_backfill_legacy_posts_without_category_to_english_self_custody.php');

    $migration->up();

    expect($legacyPost->refresh()->category_id)->toBe($englishSelfCustody->id)
        ->and($legacyPost->category?->lang)->toBe(Language::English)
        ->and($legacyPost->category?->id)->not->toBe($germanSelfCustody->id)
        ->and($categorizedPost->refresh()->category_id)->toBe($otherCategory->id)
        ->and($translation->refresh()->slug)->toBe('legacy-self-custody-url');
});

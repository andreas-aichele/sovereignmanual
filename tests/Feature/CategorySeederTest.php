<?php

use App\Enums\Language;
use App\Models\Category;
use Database\Seeders\CategorySeeder;

test('category seeder creates the editorial taxonomy without a generic bitcoin category', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::query()->distinct()->orderBy('key')->pluck('key')->all())->toBe([
        'economics',
        'family-legacy',
        'financial-sovereignty',
        'mindset',
        'news',
        'privacy-security',
        'self-custody',
        'tools-practice',
    ])
        ->and(Category::query()->where('key', 'mindset')->where('lang', Language::English)->first()?->name)->toBe('Mindset')
        ->and(Category::query()->where('key', 'mindset')->where('lang', Language::German)->first()?->name)->toBe('Denkweise')
        ->and(Category::query()->where('key', 'mindset')->where('lang', Language::English)->first()?->slug)->toBe('mindset')
        ->and(Category::query()->where('key', 'mindset')->where('lang', Language::German)->first()?->slug)->toBe('denkweise')
        ->and(Category::query()->where('key', 'mindset')->count())->toBe(2)
        ->and(Category::query()->where('key', 'bitcoin')->exists())->toBeFalse();
});

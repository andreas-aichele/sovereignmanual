<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;

test('category seeder creates the editorial taxonomy without a generic bitcoin category', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::query()->distinct()->orderBy('key')->pluck('key')->all())->toBe([
        'economics',
        'family-legacy',
        'financial-sovereignty',
        'mindset',
        'privacy-security',
        'self-custody',
        'tools-practice',
    ])
        ->and(Category::query()->where('key', 'mindset')->where('lang', 'en')->first()?->name)->toBe('Mindset')
        ->and(Category::query()->where('key', 'mindset')->where('lang', 'de')->first()?->name)->toBe('Denkweise')
        ->and(Category::query()->where('key', 'mindset')->where('lang', 'en')->first()?->slug)->toBe('mindset')
        ->and(Category::query()->where('key', 'mindset')->where('lang', 'de')->first()?->slug)->toBe('denkweise')
        ->and(Category::query()->where('key', 'mindset')->count())->toBe(2)
        ->and(Category::query()->where('key', 'bitcoin')->exists())->toBeFalse();
});

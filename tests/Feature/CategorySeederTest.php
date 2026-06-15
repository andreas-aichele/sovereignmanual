<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;

test('category seeder creates the editorial taxonomy without a generic bitcoin category', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::query()->orderBy('slug')->pluck('slug')->all())->toBe([
        'economics',
        'family-legacy',
        'financial-sovereignty',
        'mindset',
        'privacy-security',
        'self-custody',
        'tools-practice',
    ])
        ->and(Category::query()->where('slug', 'mindset')->first()?->name)->toBe([
            'en' => 'Mindset',
            'de' => 'Denkweise',
        ])
        ->and(Category::query()->where('slug', 'bitcoin')->exists())->toBeFalse();
});

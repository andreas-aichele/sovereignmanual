<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Enums\Language;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        Category::query()->firstOrCreate(
            ['key' => 'self-custody', 'lang' => Language::German],
            [
                'slug' => 'selbstverwahrung',
                'name' => 'Selbstverwahrung',
                'description' => 'Praktische Orientierung für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
            ]
        );

        return [
            'content_topic_id' => ContentTopic::factory(),
            'category_id' => Category::query()->firstOrCreate(
                ['key' => 'self-custody', 'lang' => Locales::fallbackLanguage()],
                [
                    'slug' => 'self-custody',
                    'name' => 'Self Custody',
                    'description' => 'Practical guidance for holding keys, building recovery plans, and reducing custody risk.',
                ]
            )->id,
            'content_type' => ContentType::Guide,
            'status' => PostStatus::Draft,
            'audience_level' => 'beginner',
            'primary_language' => Locales::fallback(),
            'scheduled_for' => now()->addDay(),
            'seo' => ['keywords' => ['bitcoin', 'financial independence']],
            'ai_metadata' => ['provider' => 'gemini'],
            'sources' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
            'scheduled_for' => null,
        ]);
    }
}

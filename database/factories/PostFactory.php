<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $topic = fake()->sentence(4);
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
            'slug' => Str::slug($topic).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => PostStatus::Draft,
            'topic' => $topic,
            'audience_level' => 'beginner',
            'primary_language' => Locales::fallback(),
            'scheduled_for' => now()->addDay(),
            'seo' => ['keywords' => ['bitcoin', 'financial independence']],
            'ai_metadata' => ['provider' => 'gemini'],
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

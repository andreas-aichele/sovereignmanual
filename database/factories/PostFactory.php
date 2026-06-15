<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Post;
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

        return [
            'content_topic_id' => ContentTopic::factory(),
            'category_id' => Category::query()->firstOrCreate(
                ['slug' => 'self-custody'],
                ['name' => ['en' => 'Self Custody', 'de' => 'Selbstverwahrung']]
            )->id,
            'slug' => Str::slug($topic).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => PostStatus::Draft,
            'topic' => $topic,
            'audience_level' => 'beginner',
            'primary_language' => 'en',
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

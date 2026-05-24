<?php

namespace Database\Factories;

use App\Enums\ContentTopicStatus;
use App\Models\ContentTopic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentTopic>
 */
class ContentTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category' => fake()->randomElement(['bitcoin', 'financial-independence', 'self-custody']),
            'status' => ContentTopicStatus::Scheduled,
            'priority' => fake()->numberBetween(1, 10),
            'audience_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'primary_language' => 'en',
            'target_languages' => ['de'],
            'scheduled_for' => now()->addDay(),
            'brief' => fake()->paragraph(),
            'constraints' => ['tone' => 'clear, practical, non-hype'],
        ];
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContentTopicStatus::Scheduled,
            'scheduled_for' => now()->subMinute(),
        ]);
    }
}

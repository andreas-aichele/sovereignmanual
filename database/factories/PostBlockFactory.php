<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostBlock>
 */
class PostBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'locale' => 'en',
            'type' => 'section',
            'sort_order' => fake()->numberBetween(0, 10),
            'heading' => fake()->sentence(3),
            'anchor' => fake()->slug(3),
            'markdown' => fake()->paragraphs(2, true),
            'data' => [],
        ];
    }
}

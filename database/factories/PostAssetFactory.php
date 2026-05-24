<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostAsset>
 */
class PostAssetFactory extends Factory
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
            'type' => 'image',
            'disk' => 'public',
            'url' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d',
            'locale' => 'en',
            'provider' => 'gemini',
            'model' => 'gemini-image',
            'prompt' => fake()->sentence(12),
            'alt_text' => fake()->sentence(8),
            'status' => 'ready',
            'metadata' => ['source' => 'factory'],
        ];
    }
}

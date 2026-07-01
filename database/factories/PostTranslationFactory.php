<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PostTranslation>
 */
class PostTranslationFactory extends Factory
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
            'post_id' => Post::factory(),
            'locale' => Locales::fallback(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->paragraph(),
            'markdown' => "# {$title}\n\n".fake()->paragraphs(4, true),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(12),
            'seo' => ['canonical_locale' => Locales::fallback()],
        ];
    }

    public function german(): static
    {
        return $this->state(fn (array $attributes): array => [
            'locale' => 'de',
            'title' => 'DE: '.$attributes['title'],
            'slug' => 'de-'.$attributes['slug'],
        ]);
    }
}

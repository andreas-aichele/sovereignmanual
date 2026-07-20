<?php

namespace Database\Factories;

use App\Enums\ContentTopicStatus;
use App\Enums\ContentType;
use App\Enums\Language;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Support\Locales;
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
        $primaryLocale = config('magazine_ai.primary_locale', 'de');
        $primaryLocale = is_string($primaryLocale) && Locales::isSupported($primaryLocale)
            ? $primaryLocale
            : 'de';

        Category::query()->firstOrCreate(
            ['key' => 'self-custody', 'lang' => Language::German],
            [
                'slug' => 'selbstverwahrung',
                'name' => 'Selbstverwahrung',
                'description' => 'Praktische Orientierung für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
            ]
        );

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category_id' => Category::query()->firstOrCreate(
                ['key' => 'self-custody', 'lang' => Locales::fallbackLanguage()],
                [
                    'slug' => 'self-custody',
                    'name' => 'Self Custody',
                    'description' => 'Practical guidance for holding keys, building recovery plans, and reducing custody risk.',
                ]
            )->id,
            'content_type' => ContentType::Guide,
            'status' => ContentTopicStatus::Scheduled,
            'priority' => fake()->numberBetween(1, 10),
            'audience_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'primary_language' => $primaryLocale,
            'target_languages' => collect(Locales::supported())
                ->reject(fn (string $locale): bool => $locale === $primaryLocale)
                ->values()
                ->all(),
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

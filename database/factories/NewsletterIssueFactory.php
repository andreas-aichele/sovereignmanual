<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Models\NewsletterIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterIssue>
 */
class NewsletterIssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fingerprint' => hash('sha256', fake()->uuid()),
            'locale' => Language::English,
            'subject' => fake()->sentence(5),
            'intro' => fake()->paragraph(),
            'posts' => [
                [
                    'title' => fake()->sentence(5),
                    'excerpt' => fake()->paragraph(),
                    'url' => 'https://example.com/example',
                ],
            ],
            'period_start' => now()->subWeek()->startOfDay(),
            'period_end' => now()->startOfDay(),
            'queued_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Models\AiRun;
use App\Models\ContentTopic;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRun>
 */
class AiRunFactory extends Factory
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
            'content_topic_id' => ContentTopic::factory(),
            'type' => AiRunType::Draft,
            'status' => AiRunStatus::Succeeded,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'prompt' => fake()->paragraph(),
            'response' => fake()->paragraphs(2, true),
            'input' => ['locale' => 'en'],
            'output' => ['publish' => true],
            'metrics' => ['review_score' => 92],
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Enums\WaitlistSubscriberStatus;
use App\Models\WaitlistSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WaitlistSubscriber>
 */
class WaitlistSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'locale' => Language::English,
            'status' => WaitlistSubscriberStatus::Pending,
            'action_token' => Str::random(64),
            'consented_at' => now(),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WaitlistSubscriberStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WaitlistSubscriberStatus::Unsubscribed,
            'confirmed_at' => now()->subDay(),
            'unsubscribed_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\NewsletterDeliveryStatus;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterDelivery>
 */
class NewsletterDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'newsletter_issue_id' => NewsletterIssue::factory(),
            'newsletter_subscriber_id' => NewsletterSubscriber::factory(),
            'status' => NewsletterDeliveryStatus::Pending,
            'queued_at' => now(),
            'sent_at' => null,
            'failed_at' => null,
            'failure_message' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NewsletterDeliveryStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}

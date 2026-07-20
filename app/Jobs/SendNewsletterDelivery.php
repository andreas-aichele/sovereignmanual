<?php

namespace App\Jobs;

use App\Enums\NewsletterDeliveryStatus;
use App\Enums\NewsletterSubscriberStatus;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterDelivery;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewsletterDelivery implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $newsletterDeliveryId) {}

    public function uniqueId(): string
    {
        return 'newsletter-delivery-'.$this->newsletterDeliveryId;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->expireAfter(120)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $delivery = NewsletterDelivery::query()
            ->with(['issue', 'subscriber'])
            ->find($this->newsletterDeliveryId);

        if ($delivery === null || $delivery->status === NewsletterDeliveryStatus::Sent) {
            return;
        }

        if (! $delivery->subscriber->isConfirmed()
            || $delivery->subscriber->status !== NewsletterSubscriberStatus::Confirmed
            || $delivery->subscriber->locale !== $delivery->issue->locale) {
            $delivery->forceFill([
                'status' => NewsletterDeliveryStatus::Skipped,
                'failure_message' => null,
                'failed_at' => null,
            ])->save();

            return;
        }

        $delivery->forceFill([
            'status' => NewsletterDeliveryStatus::Sending,
            'failure_message' => null,
            'failed_at' => null,
        ])->save();

        Mail::to($delivery->subscriber->email)
            ->locale($delivery->issue->locale->value)
            ->send(new NewsletterIssueMail($delivery));

        $delivery->forceFill([
            'status' => NewsletterDeliveryStatus::Sent,
            'sent_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        NewsletterDelivery::query()
            ->whereKey($this->newsletterDeliveryId)
            ->where('status', NewsletterDeliveryStatus::Sending)
            ->update([
                'status' => NewsletterDeliveryStatus::Failed,
                'failed_at' => now(),
                'failure_message' => $exception?->getMessage(),
                'updated_at' => now(),
            ]);
    }
}

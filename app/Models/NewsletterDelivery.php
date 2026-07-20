<?php

namespace App\Models;

use App\Enums\NewsletterDeliveryStatus;
use Database\Factories\NewsletterDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'newsletter_issue_id',
    'newsletter_subscriber_id',
    'status',
    'queued_at',
    'sent_at',
    'failed_at',
    'failure_message',
])]
class NewsletterDelivery extends Model
{
    /** @use HasFactory<NewsletterDeliveryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => NewsletterDeliveryStatus::Pending->value,
    ];

    /**
     * @return BelongsTo<NewsletterIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(NewsletterIssue::class, 'newsletter_issue_id');
    }

    /**
     * @return BelongsTo<NewsletterSubscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_subscriber_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NewsletterDeliveryStatus::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}

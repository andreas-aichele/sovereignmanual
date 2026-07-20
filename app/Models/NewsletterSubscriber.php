<?php

namespace App\Models;

use App\Enums\Language;
use App\Enums\NewsletterSubscriberStatus;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'email',
    'locale',
    'status',
    'action_token',
    'consented_at',
    'confirmed_at',
    'unsubscribed_at',
])]
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => NewsletterSubscriberStatus::Pending->value,
    ];

    public function isPending(): bool
    {
        return $this->status === NewsletterSubscriberStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === NewsletterSubscriberStatus::Confirmed;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === NewsletterSubscriberStatus::Unsubscribed;
    }

    public function hasValidActionToken(string $token): bool
    {
        return filled($this->action_token) && hash_equals($this->action_token, $token);
    }

    public function confirm(string $token): bool
    {
        if (! $this->hasValidActionToken($token)) {
            return false;
        }

        $confirmed = self::query()
            ->whereKey($this->id)
            ->where('status', NewsletterSubscriberStatus::Pending)
            ->where('action_token', $token)
            ->update([
                'status' => NewsletterSubscriberStatus::Confirmed,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]) === 1;

        if ($confirmed) {
            $this->refresh();
        }

        return $confirmed;
    }

    public function unsubscribe(string $token): bool
    {
        if (! $this->hasValidActionToken($token)) {
            return false;
        }

        $unsubscribed = self::query()
            ->whereKey($this->id)
            ->whereIn('status', [
                NewsletterSubscriberStatus::Pending,
                NewsletterSubscriberStatus::Confirmed,
            ])
            ->where('action_token', $token)
            ->update([
                'status' => NewsletterSubscriberStatus::Unsubscribed,
                'unsubscribed_at' => now(),
                'updated_at' => now(),
            ]) === 1;

        if ($unsubscribed) {
            $this->refresh();
        }

        return $unsubscribed;
    }

    /**
     * @return HasMany<NewsletterDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locale' => Language::class,
            'status' => NewsletterSubscriberStatus::class,
            'consented_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}

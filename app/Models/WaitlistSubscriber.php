<?php

namespace App\Models;

use App\Enums\Language;
use App\Enums\WaitlistSubscriberStatus;
use Database\Factories\WaitlistSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'locale',
    'status',
    'action_token',
    'consented_at',
    'confirmed_at',
    'unsubscribed_at',
])]
class WaitlistSubscriber extends Model
{
    /** @use HasFactory<WaitlistSubscriberFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => WaitlistSubscriberStatus::Pending->value,
    ];

    public function isPending(): bool
    {
        return $this->status === WaitlistSubscriberStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === WaitlistSubscriberStatus::Confirmed;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === WaitlistSubscriberStatus::Unsubscribed;
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
            ->where('status', WaitlistSubscriberStatus::Pending)
            ->where('action_token', $token)
            ->update([
                'status' => WaitlistSubscriberStatus::Confirmed,
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
                WaitlistSubscriberStatus::Pending,
                WaitlistSubscriberStatus::Confirmed,
            ])
            ->where('action_token', $token)
            ->update([
                'status' => WaitlistSubscriberStatus::Unsubscribed,
                'unsubscribed_at' => now(),
                'updated_at' => now(),
            ]) === 1;

        if ($unsubscribed) {
            $this->refresh();
        }

        return $unsubscribed;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locale' => Language::class,
            'status' => WaitlistSubscriberStatus::class,
            'consented_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}

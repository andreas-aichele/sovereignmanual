<?php

namespace App\Models;

use App\Enums\Language;
use Database\Factories\NewsletterIssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'fingerprint',
    'locale',
    'subject',
    'intro',
    'posts',
    'period_start',
    'period_end',
    'queued_at',
])]
class NewsletterIssue extends Model
{
    /** @use HasFactory<NewsletterIssueFactory> */
    use HasFactory;

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
            'posts' => 'array',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'queued_at' => 'datetime',
        ];
    }
}

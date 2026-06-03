<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'content_topic_id',
    'slug',
    'status',
    'topic',
    'audience_level',
    'primary_language',
    'published_at',
    'scheduled_for',
    'seo',
    'ai_metadata',
])]
class Post extends Model implements Auditable
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function translation(string $locale): ?PostTranslation
    {
        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', $this->primary_language);
    }

    /**
     * @return BelongsTo<ContentTopic, $this>
     */
    public function contentTopic(): BelongsTo
    {
        return $this->belongsTo(ContentTopic::class);
    }

    /**
     * @return HasMany<PostTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    /**
     * @return HasMany<PostBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PostBlock::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<PostAsset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(PostAsset::class);
    }

    /**
     * @return HasMany<AiRun, $this>
     */
    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'seo' => 'array',
            'ai_metadata' => 'array',
        ];
    }
}

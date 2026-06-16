<?php

namespace App\Models;

use App\Enums\ContentTopicStatus;
use Database\Factories\ContentTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'title',
    'slug',
    'category_id',
    'status',
    'priority',
    'audience_level',
    'primary_language',
    'target_languages',
    'scheduled_for',
    'last_generated_at',
    'brief',
    'constraints',
])]
class ContentTopic extends Model implements Auditable
{
    /** @use HasFactory<ContentTopicFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categorySlug(): string
    {
        return $this->category?->key ?? 'self-custody';
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
            'status' => ContentTopicStatus::class,
            'target_languages' => 'array',
            'constraints' => 'array',
            'scheduled_for' => 'datetime',
            'last_generated_at' => 'datetime',
        ];
    }
}

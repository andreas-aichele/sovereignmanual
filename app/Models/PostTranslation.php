<?php

namespace App\Models;

use Database\Factories\PostTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'post_id',
    'locale',
    'title',
    'slug',
    'excerpt',
    'markdown',
    'meta_title',
    'meta_description',
    'seo',
])]
class PostTranslation extends Model implements Auditable
{
    /** @use HasFactory<PostTranslationFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seo' => 'array',
        ];
    }
}

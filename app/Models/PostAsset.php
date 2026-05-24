<?php

namespace App\Models;

use Database\Factories\PostAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'post_id',
    'type',
    'disk',
    'path',
    'url',
    'locale',
    'provider',
    'model',
    'prompt',
    'alt_text',
    'status',
    'metadata',
])]
class PostAsset extends Model implements Auditable
{
    /** @use HasFactory<PostAssetFactory> */
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
            'metadata' => 'array',
        ];
    }
}

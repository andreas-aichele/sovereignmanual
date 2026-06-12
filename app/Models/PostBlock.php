<?php

namespace App\Models;

use Database\Factories\PostBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'post_id',
    'post_asset_id',
    'locale',
    'type',
    'sort_order',
    'heading',
    'anchor',
    'markdown',
    'data',
])]
class PostBlock extends Model implements Auditable
{
    /** @use HasFactory<PostBlockFactory> */
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
     * @return BelongsTo<PostAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(PostAsset::class, 'post_asset_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}

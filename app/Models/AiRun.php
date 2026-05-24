<?php

namespace App\Models;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use Database\Factories\AiRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'post_id',
    'content_topic_id',
    'type',
    'status',
    'provider',
    'model',
    'prompt',
    'response',
    'input',
    'output',
    'metrics',
    'error',
    'started_at',
    'finished_at',
])]
class AiRun extends Model
{
    /** @use HasFactory<AiRunFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<ContentTopic, $this>
     */
    public function contentTopic(): BelongsTo
    {
        return $this->belongsTo(ContentTopic::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiRunType::class,
            'status' => AiRunStatus::class,
            'input' => 'array',
            'output' => 'array',
            'metrics' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}

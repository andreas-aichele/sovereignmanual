<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\BlogAiPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ReviewPostFreshness implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Post $post) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('post-freshness-'.$this->post->id))->expireAfter(1800)];
    }

    public function handle(BlogAiPipeline $pipeline): void
    {
        $pipeline->refreshPost($this->post);
    }
}

<?php

namespace App\Jobs;

use App\Models\ContentTopic;
use App\Services\MagazineAiPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class GeneratePostFromTopic implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ContentTopic $topic) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('content-topic-'.$this->topic->id))->expireAfter(1800)];
    }

    public function handle(MagazineAiPipeline $pipeline): void
    {
        $pipeline->generatePost($this->topic);
    }
}

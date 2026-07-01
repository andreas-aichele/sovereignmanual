<?php

namespace App\Jobs;

use App\Services\MagazineAiPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

#[FailOnTimeout]
class IdeateNewsTopicsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public function __construct(public int $count = 1) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('news-topic-ideation'))->expireAfter(600)];
    }

    public function handle(MagazineAiPipeline $pipeline): void
    {
        Log::channel('queue')->info('News topic ideation started.', $this->logContext());

        $topics = $pipeline->createNewsTopicIdeas($this->count);

        Log::channel('queue')->info('News topic ideation completed.', $this->logContext() + [
            'created_topics' => $topics->pluck('id')->all(),
            'created_topic_count' => $topics->count(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('queue')->error('News topic ideation job failed.', $this->logContext() + [
            'exception_class' => $exception !== null ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function logContext(): array
    {
        return [
            'count' => $this->count,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'timeout' => $this->timeout,
            'memory_limit' => ini_get('memory_limit'),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }
}

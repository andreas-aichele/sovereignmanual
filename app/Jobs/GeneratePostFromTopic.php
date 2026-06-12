<?php

namespace App\Jobs;

use App\Models\ContentTopic;
use App\Services\MagazineAiPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

#[DeleteWhenMissingModels]
#[FailOnTimeout]
class GeneratePostFromTopic implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1200;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

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
        Log::channel('queue')->info('Magazine post generation started.', $this->logContext());

        $pipeline->generatePost($this->topic);

        Log::channel('queue')->info('Magazine post generation completed.', $this->logContext());
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('queue')->error('Magazine post generation job failed.', $this->logContext() + [
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
            'content_topic_id' => $this->topic->id,
            'content_topic_title' => $this->topic->title,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'timeout' => $this->timeout,
            'memory_limit' => ini_get('memory_limit'),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }
}

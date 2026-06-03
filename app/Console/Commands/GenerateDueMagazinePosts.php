<?php

namespace App\Console\Commands;

use App\Enums\ContentTopicStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Models\ContentTopic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-due-magazine-posts')]
#[Description('Queue AI generation for due magazine topics.')]
class GenerateDueMagazinePosts extends Command
{
    public function handle(): int
    {
        $topics = ContentTopic::query()
            ->where('status', ContentTopicStatus::Scheduled)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderByDesc('priority')
            ->limit(5)
            ->get();

        $topics->each(fn (ContentTopic $topic) => GeneratePostFromTopic::dispatch($topic));

        $this->info("Queued {$topics->count()} topic generation jobs.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\IdeateNewsTopicsJob;
use App\Services\MagazineAiPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('app:ideate-news-topics {--count=1 : Number of researched news topic ideas to create} {--sync : Run immediately instead of dispatching a queue job}')]
#[Description('Create sourced AI-researched Bitcoin news topic ideas for scheduled publication')]
class IdeateNewsTopics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MagazineAiPipeline $pipeline): int
    {
        if (! $this->option('sync')) {
            IdeateNewsTopicsJob::dispatch((int) $this->option('count'));

            $this->components->info('Queued news topic ideation job.');

            return self::SUCCESS;
        }

        try {
            $topics = $pipeline->createNewsTopicIdeas((int) $this->option('count'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($topics->isEmpty()) {
            $this->components->error('No researched news topic met the credibility requirements.');

            return self::FAILURE;
        }

        $this->components->info("Created or matched {$topics->count()} researched news topic idea(s).");

        return self::SUCCESS;
    }
}

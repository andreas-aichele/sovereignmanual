<?php

namespace App\Console\Commands;

use App\Services\BlogAiPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ideate-blog-topics {--count=1 : Number of topic ideas to create}')]
#[Description('Create AI-generated blog topic ideas for scheduled publication')]
class IdeateBlogTopics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BlogAiPipeline $pipeline): int
    {
        $topics = $pipeline->createTopicIdeas((int) $this->option('count'));

        $this->components->info("Created or matched {$topics->count()} topic idea(s).");

        return self::SUCCESS;
    }
}

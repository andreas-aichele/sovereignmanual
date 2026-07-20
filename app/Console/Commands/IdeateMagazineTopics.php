<?php

namespace App\Console\Commands;

use App\Enums\ContentType;
use App\Services\MagazineAiPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ideate-magazine-topics {--count=1 : Number of topic ideas to create} {--type=guide : guide, checklist, or analysis}')]
#[Description('Create AI-generated magazine topic ideas for scheduled publication')]
class IdeateMagazineTopics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MagazineAiPipeline $pipeline): int
    {
        $contentType = ContentType::tryFrom((string) $this->option('type'));

        if ($contentType === null || $contentType === ContentType::Briefing) {
            $this->components->error('The type must be guide, checklist, or analysis. Use app:ideate-news-topics for sourced Bitcoin briefings.');

            return self::FAILURE;
        }

        $topics = $pipeline->createTopicIdeas((int) $this->option('count'), $contentType);

        $this->components->info("Created or matched {$topics->count()} {$contentType->value} topic idea(s).");

        return self::SUCCESS;
    }
}

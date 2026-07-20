<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\MagazineAiPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:regenerate-post-images {--all : Include drafts and unpublished posts}')]
#[Description('Replace legacy article images with calm editorial AI cover assets')]
class RegeneratePostImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MagazineAiPipeline $pipeline): int
    {
        $posts = Post::query()
            ->with(['assets', 'contentTopic'])
            ->when(! $this->option('all'), fn ($query) => $query->whereNotNull('published_at'))
            ->get();

        $posts->each(fn (Post $post) => $pipeline->regeneratePostImage($post));

        $this->components->info("Queued or created editorial cover assets for {$posts->count()} post(s).");

        return self::SUCCESS;
    }
}

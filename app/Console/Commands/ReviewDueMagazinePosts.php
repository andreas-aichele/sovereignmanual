<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Jobs\ReviewPostFreshness;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:review-due-magazine-posts')]
#[Description('Queue AI freshness reviews for published magazine posts.')]
class ReviewDueMagazinePosts extends Command
{
    public function handle(): int
    {
        $posts = Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->limit(10)
            ->get();

        $posts->each(fn (Post $post) => ReviewPostFreshness::dispatch($post));

        $this->info("Queued {$posts->count()} freshness review jobs.");

        return self::SUCCESS;
    }
}

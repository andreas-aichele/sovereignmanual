<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->with(['translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->translation('en')?->title ?? $post->topic,
                'excerpt' => $post->translation('en')?->excerpt,
                'url' => route('blog.show', $post->translation('en')?->slug ?? $post->slug),
                'image' => $post->assets->firstWhere('status', 'ready')?->url,
                'image_alt' => $post->assets->firstWhere('status', 'ready')?->alt_text,
                'audience_level' => $post->audience_level,
                'published_at' => $post->published_at?->toAtomString(),
            ]);

        return Inertia::render('Home', [
            'featuredPost' => $posts->first(),
            'latestPosts' => $posts->skip(1)->values(),
            'meta' => [
                'title' => 'Sovereign Manual',
                'description' => 'A synthwave cypherpunk field manual for Bitcoin, financial intelligence, and sovereign independence.',
            ],
        ]);
    }
}

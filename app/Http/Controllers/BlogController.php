<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request, ?string $locale = null): Response
    {
        $locale ??= 'en';

        $posts = Post::query()
            ->with(['translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12)
            ->through(fn (Post $post): array => $this->serializePostSummary($post, $locale));

        return Inertia::render('Blog/Index', [
            'locale' => $locale,
            'alternateLocale' => $locale === 'de' ? 'en' : 'de',
            'posts' => $posts,
            'meta' => [
                'title' => 'Sovereign Manual Blog',
                'description' => $locale === 'de'
                    ? 'Bitcoin, finanzielle Bildung und souveräne Unabhängigkeit.'
                    : 'Bitcoin, financial intelligence, and sovereign independence.',
            ],
        ]);
    }

    public function show(Request $request, string $slug, ?string $locale = null): Response
    {
        $locale ??= 'en';

        $post = Post::query()
            ->with(['translations', 'blocks.asset', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', fn ($query) => $query
                ->where('locale', $locale)
                ->where('slug', $slug))
            ->firstOrFail();

        $translation = $post->translation($locale);

        abort_if($translation === null, 404);

        return Inertia::render('Blog/Show', [
            'locale' => $locale,
            'post' => [
                ...$this->serializePostSummary($post, $locale),
                'markdown' => $translation->markdown,
                'blocks' => $post->blocks
                    ->where('locale', $locale)
                    ->values()
                    ->map(fn ($block): array => [
                        'id' => $block->id,
                        'type' => $block->type,
                        'markdown' => $block->markdown,
                        'data' => $block->data,
                        'asset' => $block->asset ? [
                            'url' => $block->asset->url,
                            'alt' => $block->asset->alt_text,
                        ] : null,
                    ]),
            ],
            'meta' => [
                'title' => $translation->meta_title ?: $translation->title,
                'description' => $translation->meta_description ?: $translation->excerpt,
                'canonical' => route($locale === 'de' ? 'blog.de.show' : 'blog.show', $translation->slug),
                'alternate' => $this->alternateUrl($post, $locale),
            ],
        ]);
    }

    private function serializePostSummary(Post $post, string $locale): array
    {
        $translation = $post->translation($locale);
        $routeName = $locale === 'de' ? 'blog.de.show' : 'blog.show';

        return [
            'id' => $post->id,
            'topic' => $post->topic,
            'status' => $post->status->value,
            'audience_level' => $post->audience_level,
            'published_at' => $post->published_at?->toAtomString(),
            'next_review_at' => $post->next_review_at?->toDateString(),
            'title' => $translation?->title ?? $post->topic,
            'slug' => $translation?->slug ?? $post->slug,
            'excerpt' => $translation?->excerpt,
            'url' => route($routeName, $translation?->slug ?? $post->slug),
            'image' => $post->assets->firstWhere('status', 'ready')?->url,
            'image_alt' => $post->assets->firstWhere('status', 'ready')?->alt_text,
        ];
    }

    private function alternateUrl(Post $post, string $locale): ?string
    {
        $alternateLocale = $locale === 'de' ? 'en' : 'de';
        $translation = $post->translation($alternateLocale);

        if ($translation === null) {
            return null;
        }

        return route($alternateLocale === 'de' ? 'blog.de.show' : 'blog.show', $translation->slug);
    }
}

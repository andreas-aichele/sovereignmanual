<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request, ?string $locale = null): Response
    {
        $locale ??= 'en';

        $posts = Post::query()
            ->with(['contentTopic', 'translations', 'assets'])
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
            'copy' => $this->indexCopy($locale),
            'meta' => [
                'title' => 'Sovereign Manual Magazine',
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
                'html' => $this->renderMarkdown($translation->markdown),
                'blocks' => $post->blocks
                    ->where('locale', $locale)
                    ->values()
                    ->map(fn ($block): array => [
                        'id' => $block->id,
                        'type' => $block->type,
                        'markdown' => $block->markdown,
                        'html' => $this->renderMarkdown($block->markdown),
                        'data' => $block->data,
                        'asset' => $block->asset ? [
                            'url' => $block->asset->url,
                            'alt' => $block->asset->alt_text,
                        ] : null,
                    ]),
            ],
            'copy' => [
                'back' => $locale === 'de' ? 'Zurueck ins Archiv' : 'Back to archive',
                'freshness' => $locale === 'de' ? 'Aktualitaet getrackt' : 'Freshness tracked',
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
            'category' => $post->contentTopic?->category ?? 'bitcoin',
            'category_label' => $this->categoryLabel($post->contentTopic?->category, $locale),
            'published_at' => $post->published_at?->toAtomString(),
            'next_review_at' => $post->next_review_at?->toDateString(),
            'title' => $translation?->title ?? $post->topic,
            'slug' => $translation?->slug ?? $post->slug,
            'excerpt' => $translation?->excerpt,
            'url' => route($routeName, $translation?->slug ?? $post->slug),
            'image' => $this->coverImage($post)?->url,
            'image_alt' => $this->coverImage($post)?->alt_text,
        ];
    }

    private function categoryLabel(?string $category, string $locale): string
    {
        $labels = [
            'bitcoin' => [
                'en' => 'Bitcoin',
                'de' => 'Bitcoin',
            ],
            'financial-independence' => [
                'en' => 'Financial independence',
                'de' => 'Finanzielle Unabhaengigkeit',
            ],
            'self-custody' => [
                'en' => 'Self custody',
                'de' => 'Selbstverwahrung',
            ],
        ];

        if ($category !== null && isset($labels[$category][$locale])) {
            return $labels[$category][$locale];
        }

        return Str::of($category ?? 'bitcoin')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function coverImage(Post $post): ?PostAsset
    {
        return $post->assets
            ->where('status', 'ready')
            ->first(fn (PostAsset $asset): bool => ($asset->metadata['style'] ?? null) === 'synthwave-cypherpunk');
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

    private function renderMarkdown(?string $markdown): string
    {
        return Str::of($markdown ?? '')
            ->markdown([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            ->toString();
    }

    /**
     * @return array<string, string>
     */
    private function indexCopy(string $locale): array
    {
        if ($locale === 'de') {
            return [
                'eyebrow' => 'Archiv // Research Notes',
                'heading' => 'Sovereign Manual Magazine',
                'featured' => 'Ausgewaehlte Transmission',
                'read' => 'Artikel lesen',
                'empty' => 'Noch keine veroeffentlichten Artikel.',
            ];
        }

        return [
            'eyebrow' => 'Archive // Research notes',
            'heading' => 'Sovereign Manual Magazine',
            'featured' => 'Featured transmission',
            'read' => 'Read article',
            'empty' => 'No published articles yet.',
        ];
    }
}

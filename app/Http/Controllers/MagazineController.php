<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class MagazineController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
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

        return view('magazine.index', [
            'locale' => $locale,
            'alternateLocale' => $this->translation('alternate_locale', $locale),
            'posts' => $posts,
            'copy' => $this->translationArray('index', $locale),
            'meta' => $this->translationArray('meta', $locale),
        ]);
    }

    public function show(Request $request, string $slug, ?string $locale = null): View
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

        return view('magazine.show', [
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
            'copy' => $this->translationArray('show', $locale),
            'meta' => [
                'title' => $translation->meta_title ?: $translation->title,
                'description' => $translation->meta_description ?: $translation->excerpt,
                'canonical' => route($this->translation('routes.show', $locale), $translation->slug),
                'alternate' => $this->alternateUrl($post, $locale),
            ],
        ]);
    }

    private function serializePostSummary(Post $post, string $locale): array
    {
        $translation = $post->translation($locale);
        $category = $post->contentTopic?->category ?? 'bitcoin';
        $coverImage = $this->coverImage($post);

        return [
            'id' => $post->id,
            'topic' => $post->topic,
            'status' => $post->status->value,
            'audience_level' => $post->audience_level,
            'category' => $category,
            'category_label' => $this->categoryLabel($category, $locale),
            'published_at' => $post->published_at?->toAtomString(),
            'title' => $translation?->title ?? $post->topic,
            'slug' => $translation?->slug ?? $post->slug,
            'excerpt' => $translation?->excerpt,
            'url' => route($this->translation('routes.show', $locale), $translation?->slug ?? $post->slug),
            'image' => $coverImage?->url,
            'image_alt' => $coverImage?->alt_text,
            'image_placeholder' => $this->imagePlaceholder($post, $translation?->title ?? $post->topic, $category),
        ];
    }

    private function categoryLabel(?string $category, string $locale): string
    {
        $category ??= 'bitcoin';
        $translationKey = "magazine.categories.{$category}";

        if (Lang::has($translationKey, $locale, false)) {
            return $this->translation("categories.{$category}", $locale);
        }

        return Str::of($category)
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function coverImage(Post $post): ?PostAsset
    {
        return $post->assets
            ->where('status', 'ready')
            ->first(fn (PostAsset $asset): bool => ($asset->metadata['role'] ?? null) === 'header')
            ?? $post->assets
                ->where('status', 'ready')
                ->first(fn (PostAsset $asset): bool => ($asset->metadata['style'] ?? null) === 'synthwave-cypherpunk');
    }

    /**
     * @return array<string, string>
     */
    private function imagePlaceholder(Post $post, string $title, string $category): array
    {
        $palettes = [
            'bitcoin' => ['accent' => '#F7931A', 'secondary' => '#26D9FF'],
            'financial-independence' => ['accent' => '#F7931A', 'secondary' => '#FF4FD8'],
            'self-custody' => ['accent' => '#F7931A', 'secondary' => '#42F5C8'],
        ];

        $palette = $palettes[$category] ?? $palettes['bitcoin'];

        return [
            'title' => $title,
            'category' => $category,
            'accent' => $palette['accent'],
            'secondary' => $palette['secondary'],
            'seed' => (string) $post->id,
        ];
    }

    private function alternateUrl(Post $post, string $locale): ?string
    {
        $alternateLocale = $this->translation('alternate_locale', $locale);
        $translation = $post->translation($alternateLocale);

        if ($translation === null) {
            return null;
        }

        return route($this->translation('routes.show', $alternateLocale), $translation->slug);
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

    private function translation(string $key, string $locale): string
    {
        return (string) Lang::get("magazine.{$key}", [], $locale);
    }

    /**
     * @return array<string, string>
     */
    private function translationArray(string $key, string $locale): array
    {
        /** @var array<string, string> $translation */
        $translation = Lang::get("magazine.{$key}", [], $locale);

        return $translation;
    }
}

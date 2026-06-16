<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $sitemaps = collect([
            ['loc' => route('sitemap.posts'), 'lastmod' => $this->postLastModifiedDate()],
            ['loc' => route('sitemap.categories'), 'lastmod' => $this->categoryLastModifiedDate()],
        ]);

        $xml = view('sitemap-index', ['sitemaps' => $sitemaps])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function posts(): Response
    {
        $urls = $this->publishedTranslationsQuery()
            ->get()
            ->map(fn (PostTranslation $translation): array => $this->postSitemapUrl($translation));

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function categories(): Response
    {
        $urls = Category::query()
            ->orderBy('key')
            ->get()
            ->sortBy(fn (Category $category): array => [
                $category->key,
                $category->lang->value === Locales::fallback() ? 0 : 1,
                $category->lang->value,
            ])
            ->map(fn (Category $category): array => $this->categorySitemapUrl($category));

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function postLastModifiedDate(): string
    {
        $latestPost = Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->first(['updated_at', 'published_at']);

        return $latestPost
            ? ($latestPost->updated_at ?? $latestPost->published_at)->toDateString()
            : now()->toDateString();
    }

    private function categoryLastModifiedDate(): string
    {
        $latestCategory = Category::query()
            ->latest('updated_at')
            ->first(['updated_at']);

        return $latestCategory?->updated_at?->toDateString() ?? now()->toDateString();
    }

    /**
     * @return Builder<PostTranslation>
     */
    private function publishedTranslationsQuery(): Builder
    {
        return PostTranslation::query()
            ->select('post_translations.*')
            ->with('post.category')
            ->join('posts', 'posts.id', '=', 'post_translations.post_id')
            ->where('posts.status', PostStatus::Published)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', now())
            ->orderByDesc('posts.published_at')
            ->orderBy('post_translations.locale');
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: string}
     */
    private function postSitemapUrl(PostTranslation $translation): array
    {
        return [
            'loc' => $this->localizedRoute($translation->locale, 'show', [
                'category' => $translation->post->category?->localizedSlug($translation->locale) ?? 'self-custody',
                'slug' => $translation->slug,
            ]),
            'lastmod' => ($translation->post->updated_at ?? $translation->post->published_at)?->toDateString(),
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ];
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: string}
     */
    private function categorySitemapUrl(Category $category): array
    {
        return [
            'loc' => $this->localizedRoute($category->lang->value, 'category', [
                'category' => $category->slug,
            ]),
            'lastmod' => $category->updated_at?->toDateString(),
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function localizedRoute(string $locale, string $route, array $parameters = []): string
    {
        if ($locale === Locales::fallback()) {
            return route("magazine.{$route}", $parameters);
        }

        return route("magazine.localized.{$route}", [
            'locale' => $locale,
            ...$parameters,
        ]);
    }
}

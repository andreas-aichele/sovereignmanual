<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Pillar;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    private const int MINIMUM_PILLAR_POSTS = 6;

    public function index(): Response
    {
        $sitemaps = collect([
            ['loc' => route('sitemap.posts'), 'lastmod' => $this->postLastModifiedDate()],
        ]);
        $publishedPostCountsByLocale = $this->publishedPostCountsByLocale();
        $indexableCategories = $this->indexableCategories($publishedPostCountsByLocale);
        $indexablePillars = $this->indexablePillars($publishedPostCountsByLocale);

        if ($indexableCategories->isNotEmpty()) {
            $sitemaps->push([
                'loc' => route('sitemap.categories'),
                'lastmod' => $this->categoryLastModifiedDate($indexableCategories),
            ]);
        }

        if ($indexablePillars->isNotEmpty()) {
            $sitemaps->push([
                'loc' => route('sitemap.pillars'),
                'lastmod' => $this->pillarLastModifiedDate($indexablePillars),
            ]);
        }

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
        $urls = $this->indexableCategories($this->publishedPostCountsByLocale())
            ->sortBy(fn (Category $category): array => [
                $category->key,
                $category->lang->value === Locales::fallback() ? 0 : 1,
                $category->lang->value,
            ])
            ->map(fn (Category $category): array => $this->categorySitemapUrl($category));

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function pillars(): Response
    {
        $urls = $this->indexablePillars($this->publishedPostCountsByLocale())
            ->sortBy(fn (Pillar $pillar): array => [
                $pillar->key,
                $pillar->lang->value === Locales::fallback() ? 0 : 1,
                $pillar->lang->value,
            ])
            ->map(fn (Pillar $pillar): array => $this->pillarSitemapUrl($pillar));

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

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryLastModifiedDate(Collection $categories): string
    {
        $latestCategory = $categories
            ->sortByDesc('updated_at')
            ->first();

        return $latestCategory?->updated_at?->toDateString() ?? now()->toDateString();
    }

    /**
     * @param  Collection<int, Pillar>  $pillars
     */
    private function pillarLastModifiedDate(Collection $pillars): string
    {
        $latestPillar = $pillars
            ->sortByDesc('updated_at')
            ->first();

        return $latestPillar?->updated_at?->toDateString() ?? now()->toDateString();
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
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: string}
     */
    private function pillarSitemapUrl(Pillar $pillar): array
    {
        return [
            'loc' => $this->localizedRoute($pillar->lang->value, 'pillar.show', [
                'pillar' => $pillar->slug,
            ]),
            'lastmod' => $pillar->updated_at?->toDateString(),
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    private function indexableCategories(array $publishedPostCountsByLocale): Collection
    {
        return Category::query()
            ->get()
            ->filter(fn (Category $category): bool => ($publishedPostCountsByLocale[$category->lang->value][$category->key] ?? 0) > 0)
            ->values();
    }

    /**
     * @return Collection<int, Pillar>
     */
    private function indexablePillars(array $publishedPostCountsByLocale): Collection
    {
        return Pillar::query()
            ->with('categories:id,pillar_id,key')
            ->get(['id', 'key', 'lang', 'slug', 'name', 'description', 'updated_at'])
            ->filter(fn (Pillar $pillar): bool => $this->publishedPostCountForPillar(
                $pillar,
                $publishedPostCountsByLocale[$pillar->lang->value] ?? [],
            ) >= self::MINIMUM_PILLAR_POSTS)
            ->values();
    }

    /**
     * @param  array<string, int>  $publishedPostCounts
     */
    private function publishedPostCountForPillar(Pillar $pillar, array $publishedPostCounts): int
    {
        return $pillar->categories
            ->pluck('key')
            ->unique()
            ->sum(fn (string $categoryKey): int => $publishedPostCounts[$categoryKey] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    private function publishedPostCountsByLocale(): array
    {
        return collect(Locales::supported())
            ->mapWithKeys(fn (string $locale): array => [
                $locale => $this->publishedPostCountsByCategoryKey($locale),
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function publishedPostCountsByCategoryKey(string $locale): array
    {
        $postTable = (new Post)->getTable();
        $categoryTable = (new Category)->getTable();

        return Post::query()
            ->select("{$categoryTable}.key")
            ->selectRaw('count(*) as post_count')
            ->join($categoryTable, "{$categoryTable}.id", '=', "{$postTable}.category_id")
            ->where("{$postTable}.status", PostStatus::Published)
            ->whereNotNull("{$postTable}.published_at")
            ->where("{$postTable}.published_at", '<=', now())
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->groupBy("{$categoryTable}.key")
            ->pluck('post_count', 'key')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
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

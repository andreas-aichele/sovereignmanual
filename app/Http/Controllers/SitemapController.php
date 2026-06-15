<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $lastModified = $this->sitemapLastModifiedDate();
        $pageCount = max(1, (int) ceil($this->sitemapUrlCount() / $this->sitemapPageSize()));

        $sitemaps = collect(range(1, $pageCount))
            ->map(fn (int $page): array => [
                'loc' => route('sitemap.page', $page),
                'lastmod' => $lastModified,
            ]);

        $xml = view('sitemap-index', ['sitemaps' => $sitemaps])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function page(int $page): Response
    {
        $pageSize = $this->sitemapPageSize();
        $urlCount = $this->sitemapUrlCount();
        $pageCount = max(1, (int) ceil($urlCount / $pageSize));

        abort_if($page < 1 || $page > $pageCount, 404);

        $offset = ($page - 1) * $pageSize;
        $urls = collect();

        if ($offset === 0) {
            $urls->push([
                'loc' => route('magazine.index'),
                'lastmod' => $this->sitemapLastModifiedDate(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ]);
        }

        $translationLimit = $pageSize - $urls->count();

        if ($translationLimit > 0) {
            $this->publishedTranslationsQuery()
                ->skip(max(0, $offset - 1))
                ->take($translationLimit)
                ->get()
                ->each(function (PostTranslation $translation) use ($urls): void {
                    $urls->push($this->sitemapUrlForTranslation($translation));
                });
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function sitemapPageSize(): int
    {
        return max(1, (int) config('app.sitemap_per_page', 1000));
    }

    private function sitemapUrlCount(): int
    {
        return 1 + $this->publishedTranslationsQuery()->count();
    }

    private function sitemapLastModifiedDate(): string
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
    private function sitemapUrlForTranslation(PostTranslation $translation): array
    {
        return [
            'loc' => route($this->translation('routes.show', $translation->locale), [
                'category' => $translation->post->category?->slug ?? 'self-custody',
                'slug' => $translation->slug,
            ]),
            'lastmod' => ($translation->post->updated_at ?? $translation->post->published_at)?->toDateString(),
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ];
    }

    private function translation(string $key, string $locale): string
    {
        return (string) Lang::get("magazine.{$key}", [], $locale);
    }
}

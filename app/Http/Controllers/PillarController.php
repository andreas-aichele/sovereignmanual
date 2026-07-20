<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Pillar;
use App\Models\Post;
use App\Models\PostAsset;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PillarController extends Controller
{
    public function show(string $localeOrPillar, ?string $pillar = null): View
    {
        $locale = App::currentLocale();
        $pillar ??= $localeOrPillar;
        $resolvedPillar = $this->findPillar($pillar, $locale);
        $categoryKeys = Category::query()
            ->where('pillar_id', $resolvedPillar->id)
            ->pluck('key');
        $posts = $this->publishedPostsQuery($locale)
            ->whereHas('category', fn (Builder $query) => $query->whereIn('key', $categoryKeys))
            ->paginate(12)
            ->through(fn (Post $post): array => $this->serializePostSummary($post, $locale));
        $isIndexable = $posts->total() >= 6;
        $alternates = $this->alternates($resolvedPillar);
        $canonical = $this->localizedRoute($locale, 'show', [
            'pillar' => $resolvedPillar->localizedSlug($locale),
        ]);

        return view('magazine.pillar', [
            'locale' => $locale,
            'languageOptions' => $this->languageOptions($locale, $resolvedPillar),
            'pillar' => [
                'key' => $resolvedPillar->key,
                'title' => $resolvedPillar->label($locale),
                'description' => $resolvedPillar->localizedDescription($locale),
            ],
            'posts' => $posts,
            'copy' => $this->translationArray('index', $locale),
            'meta' => [
                'title' => $this->truncateMeta($resolvedPillar->label($locale), 60),
                'description' => $this->truncateMeta($resolvedPillar->localizedDescription($locale), 160),
                'canonical' => $canonical,
                'alternates' => $alternates,
                'xDefault' => $alternates[Locales::fallback()] ?? $canonical,
                'ogType' => 'website',
                'ogLocale' => Locales::language($locale)->openGraphLocale(),
                'ogLocaleAlternates' => collect(Locales::supported())
                    ->reject(fn (string $alternateLocale): bool => $alternateLocale === $locale)
                    ->map(fn (string $alternateLocale): string => Locales::language($alternateLocale)->openGraphLocale())
                    ->values()
                    ->all(),
                'robots' => $isIndexable ? 'index, follow' : 'noindex, follow',
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $resolvedPillar->label($locale),
                    'description' => $resolvedPillar->localizedDescription($locale),
                    'url' => $canonical,
                    'inLanguage' => $locale,
                ],
            ],
        ]);
    }

    /**
     * @return Builder<Post>
     */
    private function publishedPostsQuery(string $locale): Builder
    {
        return Post::query()
            ->with(['category', 'translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->latest('published_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePostSummary(Post $post, string $locale): array
    {
        $translation = $post->translation($locale);
        $category = $post->category?->localizedSlug($locale) ?? 'self-custody';
        $coverImage = $post->assets
            ->where('status', 'ready')
            ->first(fn (PostAsset $asset): bool => ($asset->metadata['role'] ?? null) === 'header')
            ?? $post->assets->where('status', 'ready')->first();
        $contentType = $post->content_type?->value ?? 'guide';

        return [
            'category_label' => $post->category?->label($locale) ?? Str::headline($category),
            'content_type' => $contentType,
            'content_type_label' => $this->translation("content_types.{$contentType}", $locale),
            'excerpt' => $translation?->excerpt,
            'image' => $coverImage ? $this->assetUrl($coverImage) : asset('fallback.jpg'),
            'image_alt' => $coverImage?->metadata['alt_texts'][$locale] ?? $coverImage?->alt_text ?? $translation?->title ?? '',
            'image_responsive' => $coverImage?->metadata['responsive_image'] ?? null,
            'title' => $translation?->title ?? 'Untitled article',
            'url' => $this->postRoute($locale, $category, $translation?->slug ?? ''),
        ];
    }

    private function assetUrl(PostAsset $asset): ?string
    {
        if ($asset->path !== null && $asset->disk !== null) {
            return Storage::disk($asset->disk)->url($asset->path);
        }

        return $asset->url;
    }

    private function findPillar(string $slug, string $locale): Pillar
    {
        return Pillar::query()
            ->where('lang', Locales::language($locale))
            ->where(function (Builder $query) use ($slug): void {
                $query->where('slug', $slug)->orWhere('key', $slug);
            })
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function alternates(Pillar $pillar): array
    {
        return Pillar::query()
            ->where('key', $pillar->key)
            ->get()
            ->filter(fn (Pillar $localizedPillar): bool => $this->isIndexableForLocale(
                $localizedPillar,
                $localizedPillar->lang->value,
            ))
            ->mapWithKeys(fn (Pillar $localizedPillar): array => [
                $localizedPillar->lang->value => $this->localizedRoute($localizedPillar->lang->value, 'show', [
                    'pillar' => $localizedPillar->slug,
                ]),
            ])
            ->all();
    }

    /**
     * @return array<int, array{locale: string, label: string, url: string, current: bool}>
     */
    private function languageOptions(string $currentLocale, Pillar $pillar): array
    {
        $localizedPillars = Pillar::query()
            ->where('key', $pillar->key)
            ->get()
            ->keyBy(fn (Pillar $localizedPillar): string => $localizedPillar->lang->value);

        return collect(Locales::supported())
            ->map(function (string $locale) use ($currentLocale, $localizedPillars): ?array {
                $localizedPillar = $localizedPillars->get($locale);

                if (! $localizedPillar instanceof Pillar) {
                    return null;
                }

                if ($locale !== $currentLocale && ! $this->isIndexableForLocale($localizedPillar, $locale)) {
                    return null;
                }

                return [
                    'locale' => $locale,
                    'label' => Locales::language($locale)->nativeName(),
                    'url' => $this->localizedRoute($locale, 'show', [
                        'pillar' => $localizedPillar->slug,
                    ]),
                    'current' => $locale === $currentLocale,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function isIndexableForLocale(Pillar $pillar, string $locale): bool
    {
        $categoryKeys = Category::query()
            ->where('pillar_id', $pillar->id)
            ->pluck('key');

        return Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->whereHas('category', fn (Builder $query) => $query->whereIn('key', $categoryKeys))
            ->count() >= 6;
    }

    private function postRoute(string $locale, string $category, string $slug): string
    {
        if ($locale === Locales::fallback()) {
            return route('magazine.show', compact('category', 'slug'));
        }

        return route('magazine.localized.show', compact('locale', 'category', 'slug'));
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function explicitLocalizedRoute(string $locale, string $route, array $parameters = []): string
    {
        return route("magazine.localized.pillar.{$route}", [
            'locale' => $locale,
            ...$parameters,
        ]);
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function localizedRoute(string $locale, string $route, array $parameters = []): string
    {
        if ($locale === Locales::fallback()) {
            return route("magazine.pillar.{$route}", $parameters);
        }

        return $this->explicitLocalizedRoute($locale, $route, $parameters);
    }

    /**
     * @return array<string, mixed>
     */
    private function translationArray(string $key, string $locale): array
    {
        /** @var array<string, mixed> $translation */
        $translation = trans("magazine.{$key}", [], $locale);

        return $translation;
    }

    private function translation(string $key, string $locale): string
    {
        return (string) trans("magazine.{$key}", [], $locale);
    }

    private function truncateMeta(string $value, int $limit): string
    {
        return Str::of(strip_tags($value))
            ->squish()
            ->limit($limit, '')
            ->toString();
    }
}

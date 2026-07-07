<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostAsset;
use App\Models\PostBlock;
use App\Models\PostTranslation;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MagazineController extends Controller
{
    public function index(): View
    {
        $locale = App::currentLocale();

        $posts = Post::query()
            ->with(['category', 'translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12)
            ->through(fn (Post $post): array => $this->serializePostSummary($post, $locale));

        return view('magazine.index', [
            'locale' => $locale,
            'languageOptions' => $this->languageOptions($locale),
            'posts' => $posts,
            'copy' => $this->translationArray('index', $locale),
            'meta' => [
                ...$this->translationArray('meta', $locale),
                'canonical' => $this->indexCanonical($locale),
                'alternates' => $this->indexAlternates(),
                'xDefault' => route('magazine.index'),
                'ogType' => 'website',
                'ogLocale' => $this->openGraphLocale($locale),
                'ogLocaleAlternates' => $this->openGraphLocaleAlternates($locale),
                'structuredData' => $this->websiteStructuredData($locale),
            ],
        ]);
    }

    public function show(string $localeOrCategory, string $categoryOrSlug, ?string $slug = null): View
    {
        $locale = App::currentLocale();
        $category = $slug === null ? $localeOrCategory : $categoryOrSlug;
        $slug ??= $categoryOrSlug;
        $resolvedCategory = $this->findCategory($category, $locale);

        $post = Post::query()
            ->with(['category', 'translations', 'blocks.asset', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $query) use ($resolvedCategory): void {
                $query->whereHas('category', fn (Builder $query) => $query->where('key', $resolvedCategory->key));

                if ($resolvedCategory->key === 'self-custody') {
                    $query->orWhereNull('category_id');
                }
            })
            ->whereHas('translations', fn ($query) => $query
                ->where('locale', $locale)
                ->where('slug', $slug))
            ->firstOrFail();

        $translation = $post->translation($locale);

        abort_if($translation === null, 404);

        $usedHeadingIds = [];
        $tableOfContents = [];
        $localizedBlocks = $post->blocks
            ->where('locale', $locale)
            ->values();
        $article = $localizedBlocks->isEmpty()
            ? $this->renderMarkdownWithTableOfContents($translation->markdown, $usedHeadingIds)
            : ['html' => '', 'toc' => []];
        $tableOfContents = $article['toc'];
        $blocks = $localizedBlocks
            ->map(function (PostBlock $block) use ($locale, &$usedHeadingIds, &$tableOfContents): array {
                $renderedBlock = $this->renderBlock($block, $usedHeadingIds);
                $tableOfContents = [
                    ...$tableOfContents,
                    ...$renderedBlock['toc'],
                ];

                return [
                    'id' => $block->id,
                    'type' => $block->type,
                    'markdown' => $block->markdown,
                    'heading' => $block->heading,
                    'anchor' => $block->anchor,
                    'html' => $renderedBlock['html'],
                    'data' => $block->data,
                    'asset' => $block->asset ? [
                        'url' => $this->assetUrl($block->asset),
                        'alt' => $this->assetAltText($block->asset, $locale),
                        'responsive' => $block->asset->metadata['responsive_image'] ?? null,
                    ] : null,
                ];
            });

        return view('magazine.show', [
            'locale' => $locale,
            'post' => [
                ...$this->serializePostSummary($post, $locale),
                'markdown' => $translation->markdown,
                'html' => $article['html'],
                'blocks' => $blocks,
                'toc' => $tableOfContents,
            ],
            'languageOptions' => $this->languageOptions($locale, $post),
            'copy' => $this->translationArray('show', $locale),
            'meta' => [
                'title' => $this->truncateMeta($translation->meta_title ?: $translation->title, 60),
                'description' => $this->truncateMeta($translation->meta_description ?: $translation->excerpt, 160),
                'keywords' => $translation->seo['keywords'] ?? $post->seo['keywords'] ?? [],
                'canonical' => $this->localizedRoute($locale, 'show', [
                    'category' => $this->categorySlug($post->category, $locale),
                    'slug' => $translation->slug,
                ]),
                'alternates' => $this->postAlternates($post),
                'xDefault' => $this->xDefaultPostUrl($post),
                'alternate' => $this->alternateUrl($post, $locale),
                'ogType' => 'article',
                'ogLocale' => $this->openGraphLocale($locale),
                'ogLocaleAlternates' => $this->openGraphLocaleAlternates($locale),
                'ogImage' => $this->absoluteUrl($this->serializePostSummary($post, $locale)['image']),
                'author' => 'Sovereign Manual',
                'articlePublishedTime' => $post->published_at?->toAtomString(),
                'articleModifiedTime' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
                'articleSection' => $this->serializePostSummary($post, $locale)['category_label'],
                'structuredData' => $this->articleStructuredData($post, $translation, $locale),
            ],
        ]);
    }

    public function category(string $localeOrCategory, ?string $category = null): View
    {
        $locale = App::currentLocale();
        $category ??= $localeOrCategory;
        $resolvedCategory = $this->findCategory($category, $locale);

        $posts = Post::query()
            ->with(['category', 'translations', 'assets'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $query) use ($resolvedCategory): void {
                $query->whereHas('category', fn (Builder $query) => $query->where('key', $resolvedCategory->key));

                if ($resolvedCategory->key === 'self-custody') {
                    $query->orWhereNull('category_id');
                }
            })
            ->latest('published_at')
            ->paginate(12)
            ->through(fn (Post $post): array => $this->serializePostSummary($post, $locale));

        return view('magazine.category', [
            'locale' => $locale,
            'languageOptions' => $this->languageOptions($locale),
            'category' => [
                'key' => $resolvedCategory->key,
                'slug' => $resolvedCategory->localizedSlug($locale),
                'title' => $resolvedCategory->label($locale),
                'description' => $resolvedCategory->localizedDescription($locale),
            ],
            'posts' => $posts,
            'copy' => $this->translationArray('index', $locale),
            'meta' => [
                'title' => $this->truncateMeta($resolvedCategory->label($locale), 60),
                'description' => $this->truncateMeta($resolvedCategory->localizedDescription($locale), 160),
                'canonical' => $this->localizedRoute($locale, 'category', [
                    'category' => $resolvedCategory->localizedSlug($locale),
                ]),
                'alternates' => $this->categoryAlternates($resolvedCategory),
                'xDefault' => $this->localizedRoute(Locales::fallback(), 'category', [
                    'category' => $resolvedCategory->localizedSlug(Locales::fallback()),
                ]),
                'ogType' => 'website',
                'ogLocale' => $this->openGraphLocale($locale),
                'ogLocaleAlternates' => $this->openGraphLocaleAlternates($locale),
                'structuredData' => $this->collectionStructuredData(
                    $resolvedCategory->label($locale),
                    $resolvedCategory->localizedDescription($locale),
                    $this->localizedRoute($locale, 'category', [
                        'category' => $resolvedCategory->localizedSlug($locale),
                    ]),
                ),
            ],
        ]);
    }

    public function switchLocale(): View
    {
        return $this->index();
    }

    private function serializePostSummary(Post $post, string $locale): array
    {
        $translation = $post->translation($locale);
        $category = $this->categorySlug($post->category, $locale);
        $coverImage = $this->coverImage($post);
        $title = $translation?->title ?? $post->topic;

        return [
            'id' => $post->id,
            'topic' => $post->topic,
            'status' => $post->status->value,
            'audience_level' => $post->audience_level,
            'category' => $category,
            'category_label' => $post->category?->label($locale) ?? $this->categoryLabel($category),
            'category_url' => $this->localizedRoute($locale, 'category', [
                'category' => $category,
            ]),
            'published_at' => $post->published_at?->toAtomString(),
            'title' => $title,
            'slug' => $translation?->slug ?? $post->slug,
            'excerpt' => $translation?->excerpt,
            'url' => $this->localizedRoute($locale, 'show', [
                'category' => $category,
                'slug' => $translation?->slug ?? $post->slug,
            ]),
            'image' => $coverImage ? $this->assetUrl($coverImage) : asset('fallback.jpg'),
            'image_alt' => $coverImage ? $this->assetAltText($coverImage, $locale, $title) : $title,
            'image_responsive' => $coverImage?->metadata['responsive_image'] ?? null,
        ];
    }

    private function assetAltText(PostAsset $asset, string $locale, ?string $fallback = null): string
    {
        $localizedAltText = $asset->metadata['alt_texts'][$locale] ?? null;

        if (is_string($localizedAltText) && filled($localizedAltText)) {
            return $localizedAltText;
        }

        return $asset->alt_text ?? $fallback ?? '';
    }

    private function assetUrl(PostAsset $asset): ?string
    {
        if ($asset->path !== null && $asset->disk !== null) {
            return Storage::disk($asset->disk)->url($asset->path);
        }

        return $asset->url;
    }

    private function categoryLabel(?string $category): string
    {
        $category ??= 'self-custody';

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

    private function indexCanonical(string $locale): string
    {
        if (request()->route('locale') === $locale) {
            return $this->explicitLocalizedRoute($locale, 'index');
        }

        return $locale === Locales::fallback()
            ? route('magazine.index')
            : $this->explicitLocalizedRoute($locale, 'index');
    }

    /**
     * @return array<string, string>
     */
    private function indexAlternates(): array
    {
        return collect(Locales::supported())
            ->mapWithKeys(fn (string $locale): array => [
                $locale => $this->explicitLocalizedRoute($locale, 'index'),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function postAlternates(Post $post): array
    {
        return collect(Locales::supported())
            ->mapWithKeys(function (string $locale) use ($post): array {
                $translation = $this->exactTranslation($post, $locale);

                if ($translation === null) {
                    return [];
                }

                return [
                    $locale => $this->explicitLocalizedRoute($locale, 'show', [
                        'category' => $this->categorySlug($post->category, $locale),
                        'slug' => $translation->slug,
                    ]),
                ];
            })
            ->all();
    }

    private function xDefaultPostUrl(Post $post): string
    {
        $alternates = $this->postAlternates($post);

        return $alternates[Locales::fallback()] ?? (reset($alternates) ?: route('magazine.index'));
    }

    private function openGraphLocale(string $locale): string
    {
        return Locales::language($locale)->openGraphLocale();
    }

    /**
     * @return array<int, string>
     */
    private function openGraphLocaleAlternates(string $currentLocale): array
    {
        return collect(Locales::supported())
            ->reject(fn (string $locale): bool => $locale === $currentLocale)
            ->map(fn (string $locale): string => $this->openGraphLocale($locale))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function categoryAlternates(Category $category): array
    {
        $localizedCategories = Category::query()
            ->where('key', $category->key)
            ->get()
            ->keyBy(fn (Category $category): string => $category->lang->value);

        return collect(Locales::supported())
            ->mapWithKeys(function (string $locale) use ($localizedCategories): array {
                $category = $localizedCategories->get($locale);

                if (! $category instanceof Category) {
                    return [];
                }

                return [
                    $locale => $this->explicitLocalizedRoute($locale, 'category', [
                        'category' => $category->slug,
                    ]),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteStructuredData(string $locale): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Sovereign Manual',
            'url' => $this->indexCanonical($locale),
            'inLanguage' => $locale,
            'description' => $this->translation('meta.description', $locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionStructuredData(string $title, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $title,
            'url' => $url,
            'description' => $description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function articleStructuredData(Post $post, PostTranslation $translation, string $locale): array
    {
        $summary = $this->serializePostSummary($post, $locale);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation->title,
            'description' => $this->truncateMeta($translation->meta_description ?: $translation->excerpt, 160),
            'image' => $this->absoluteUrl($summary['image']),
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            'inLanguage' => $locale,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $summary['url'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Sovereign Manual',
                'url' => route('magazine.index'),
            ],
        ];
    }

    private function absoluteUrl(?string $url): ?string
    {
        if ($url === null || Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }

    private function alternateUrl(Post $post, string $locale): ?string
    {
        $alternateLocale = collect(Locales::supported())
            ->reject(fn (string $alternateLocale): bool => $alternateLocale === $locale)
            ->first(fn (string $alternateLocale): bool => $this->exactTranslation($post, $alternateLocale) !== null);

        if (! is_string($alternateLocale)) {
            return null;
        }

        $translation = $this->exactTranslation($post, $alternateLocale);

        if ($translation === null) {
            return null;
        }

        return $this->explicitLocalizedRoute($alternateLocale, 'show', [
            'category' => $this->categorySlug($post->category, $alternateLocale),
            'slug' => $translation->slug,
        ]);
    }

    /**
     * @return array<int, array{locale: string, label: string, url: string, current: bool}>
     */
    private function languageOptions(string $currentLocale, ?Post $post = null): array
    {
        return collect(Locales::supported())
            ->map(function (string $locale) use ($currentLocale, $post): ?array {
                $translation = $post === null ? null : $this->exactTranslation($post, $locale);

                if ($post !== null && $translation === null) {
                    return null;
                }

                return [
                    'locale' => $locale,
                    'label' => Locales::language($locale)->nativeName(),
                    'url' => $translation === null
                        ? $this->localeSwitchUrl($locale)
                        : $this->explicitLocalizedRoute($locale, 'show', [
                            'category' => $this->categorySlug($post->category, $locale),
                            'slug' => $translation->slug,
                        ]),
                    'current' => $locale === $currentLocale,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function localeSwitchUrl(string $locale): string
    {
        return $this->explicitLocalizedRoute($locale, 'index');
    }

    private function exactTranslation(Post $post, string $locale): ?PostTranslation
    {
        return $post->translations->firstWhere('locale', $locale);
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function explicitLocalizedRoute(string $locale, string $route, array $parameters = []): string
    {
        return route("magazine.localized.{$route}", [
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
            return route("magazine.{$route}", $parameters);
        }

        return route("magazine.localized.{$route}", [
            'locale' => $locale,
            ...$parameters,
        ]);
    }

    private function findCategory(string $slug, string $locale): Category
    {
        $language = Locales::language($locale);

        return Category::query()
            ->where('lang', $language)
            ->where(function (Builder $query) use ($slug): void {
                $query->where('slug', $slug)
                    ->orWhere('key', $slug);
            })
            ->firstOrFail();
    }

    private function categorySlug(?Category $category, string $locale): string
    {
        return $category?->localizedSlug($locale) ?? 'self-custody';
    }

    /**
     * @param  array<string, int>  $usedHeadingIds
     * @return array{html: string, toc: array<int, array{id: string, title: string, level: int}>}
     */
    private function renderBlock(PostBlock $block, array &$usedHeadingIds): array
    {
        if ($block->type === 'flow_diagram') {
            return [
                'html' => $this->renderFlowDiagramData($block->data),
                'toc' => [],
            ];
        }

        $structuredHtml = match ($block->type) {
            'insight' => $this->renderInsightData($block->data),
            'checklist' => $this->renderChecklistData($block->data),
            'sketch' => $this->renderSketchData($block->data),
            default => '',
        };

        if ($structuredHtml !== '') {
            return [
                'html' => $structuredHtml,
                'toc' => [],
            ];
        }

        if (! in_array($block->type, ['section', 'markdown'], true) && trim((string) $block->markdown) === '') {
            return [
                'html' => '',
                'toc' => [],
            ];
        }

        $toc = [];
        $heading = $this->cleanHeading($block->heading);
        $headingHtml = '';

        if ($heading !== null) {
            $id = $this->uniqueHeadingId($block->anchor ?: $heading, $usedHeadingIds);
            $toc[] = [
                'id' => $id,
                'title' => $heading,
                'level' => 2,
            ];
            $headingHtml = '<h2 id="'.$id.'">'.e($heading).'</h2>';
        }

        $renderedMarkdown = $this->renderMarkdownWithTableOfContents($block->markdown, $usedHeadingIds);

        return [
            'html' => $headingHtml.$renderedMarkdown['html'],
            'toc' => [
                ...$toc,
                ...$renderedMarkdown['toc'],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $usedHeadingIds
     * @return array{html: string, toc: array<int, array{id: string, title: string, level: int}>}
     */
    private function renderMarkdownWithTableOfContents(?string $markdown, array &$usedHeadingIds): array
    {
        $toc = [];
        $html = Str::of($markdown ?? '')
            ->markdown([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            ->toString();

        $html = preg_replace_callback(
            '/<h2>(.*?)<\/h2>/s',
            function (array $matches) use (&$toc, &$usedHeadingIds): string {
                $htmlTitle = $matches[1];
                $title = trim(html_entity_decode(strip_tags($htmlTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($title === '') {
                    return $matches[0];
                }

                $id = $this->uniqueHeadingId($title, $usedHeadingIds);
                $toc[] = [
                    'id' => $id,
                    'title' => $title,
                    'level' => 2,
                ];

                return "<h2 id=\"{$id}\">{$htmlTitle}</h2>";
            },
            $html
        );

        return [
            'html' => $html ?? '',
            'toc' => $toc,
        ];
    }

    private function renderMarkdown(?string $markdown): string
    {
        $html = Str::of($markdown ?? '')
            ->markdown([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            ->toString();

        return $html;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function renderInsightData(?array $data): string
    {
        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        $body = is_scalar($data['body'] ?? null) ? trim((string) $data['body']) : '';

        if ($title === '' && $body === '') {
            return '';
        }

        $titleHtml = $title === '' ? '' : '<h3 class="m-0 text-base font-semibold">'.e($title).'</h3>';
        $bodyHtml = $body === '' ? '' : '<p class="text-base-content/80 m-0">'.e($body).'</p>';

        return '<aside class="rounded-box border border-info/25 bg-info/10 p-5"><div class="flex flex-col gap-2">'.$titleHtml.$bodyHtml.'</div></aside>';
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function renderChecklistData(?array $data): string
    {
        $items = collect($data['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_scalar($item) && trim((string) $item) !== '')
            ->map(fn (mixed $item): string => trim((string) $item))
            ->values()
            ->all();

        if ($items === []) {
            return '';
        }

        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        $titleHtml = $title === '' ? '' : '<h3 class="m-0 text-base font-semibold">'.e($title).'</h3>';
        $itemsHtml = collect($items)
            ->map(fn (string $item, int $index): string => '<li class="rounded-box border border-base-300 bg-base-200 p-3 text-base-content shadow-sm"><div class="flex items-start gap-3"><span class="badge badge-primary h-7 w-7 shrink-0 rounded-full p-0 font-semibold">'.($index + 1).'</span><span class="pt-0.5 font-medium">'.e($item).'</span></div></li>')
            ->implode('');

        return '<aside class="rounded-box border border-primary/40 bg-base-100 p-5 shadow-sm"><div class="flex flex-col gap-4">'.$titleHtml.'<ol class="m-0 grid list-none gap-2 p-0">'.$itemsHtml.'</ol></div></aside>';
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function renderSketchData(?array $data): string
    {
        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        $caption = is_scalar($data['caption'] ?? null) ? trim((string) $data['caption']) : '';
        $labels = collect($data['labels'] ?? [])
            ->filter(fn (mixed $label): bool => is_scalar($label) && trim((string) $label) !== '')
            ->map(fn (mixed $label): string => trim((string) $label))
            ->values()
            ->all();

        if ($title === '' && $caption === '' && $labels === []) {
            return '';
        }

        $titleHtml = $title === '' ? '' : '<h3 class="m-0 text-base font-semibold">'.e($title).'</h3>';
        $captionHtml = $caption === '' ? '' : '<p class="text-base-content/75 m-0">'.e($caption).'</p>';
        $labelsHtml = collect($labels)
            ->map(fn (string $label): string => '<span class="badge badge-outline">'.e($label).'</span>')
            ->implode('');
        $labelGroupHtml = $labelsHtml === '' ? '' : '<div class="flex flex-wrap gap-2">'.$labelsHtml.'</div>';

        return '<aside class="rounded-box border border-base-300 bg-base-200/70 p-5"><div class="flex flex-col gap-3">'.$titleHtml.$captionHtml.$labelGroupHtml.'</div></aside>';
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function renderFlowDiagramData(?array $data): string
    {
        $rows = $this->flowDiagramRows($data);

        if ($rows === []) {
            return '';
        }

        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        $direction = $this->flowDiagramDirection($data);

        return $this->renderMermaidBlock($this->mermaidFlowchart($rows, $title, $direction));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function flowDiagramRows(?array $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $diagram = is_array($data['diagram'] ?? null) ? $data['diagram'] : $data;

        if (($diagram['kind'] ?? 'flowchart') !== 'flowchart') {
            return [];
        }

        $rows = collect($diagram['rows'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(fn (array $row): array => collect($row)
                ->filter(fn (mixed $step): bool => is_scalar($step) && trim((string) $step) !== '')
                ->map(fn (mixed $step): string => trim((string) $step))
                ->values()
                ->all())
            ->filter(fn (array $row): bool => count($row) >= 2)
            ->values()
            ->all();

        if ($rows !== []) {
            return $rows;
        }

        $steps = collect($diagram['steps'] ?? [])
            ->filter(fn (mixed $step): bool => is_scalar($step) && trim((string) $step) !== '')
            ->map(fn (mixed $step): string => trim((string) $step))
            ->filter()
            ->values()
            ->all();

        return count($steps) >= 2 ? [$steps] : [];
    }

    private function flowDiagramDirection(?array $data): string
    {
        if (! is_array($data)) {
            return 'LR';
        }

        $diagram = is_array($data['diagram'] ?? null) ? $data['diagram'] : $data;
        $direction = is_scalar($diagram['direction'] ?? null) ? strtoupper(trim((string) $diagram['direction'])) : 'LR';

        return in_array($direction, ['LR', 'RL', 'TB', 'TD', 'BT'], true) ? $direction : 'LR';
    }

    private function mermaidNodeId(int $rowIndex, int $columnIndex): string
    {
        return "node_{$rowIndex}_{$columnIndex}";
    }

    private function mermaidLabel(string $label): string
    {
        return str_replace('"', '#quot;', $label);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function mermaidFlowchart(array $rows, string $title = '', string $direction = 'LR'): string
    {
        $lines = ["flowchart {$direction}"];
        $nodeIds = [];

        if ($title !== '') {
            $lines[] = '%% '.$title;
        }

        foreach ($rows as $rowIndex => $row) {
            for ($columnIndex = 0; $columnIndex < count($row) - 1; $columnIndex++) {
                $fromLabel = $this->mermaidLabel($row[$columnIndex]);
                $toLabel = $this->mermaidLabel($row[$columnIndex + 1]);
                $fromId = $this->sharedMermaidNodeId($nodeIds, $row[$columnIndex], $rowIndex, $columnIndex);
                $toId = $this->sharedMermaidNodeId($nodeIds, $row[$columnIndex + 1], $rowIndex, $columnIndex + 1);

                $lines[] = "    {$fromId}[\"{$fromLabel}\"] --> {$toId}[\"{$toLabel}\"]";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $nodeIds
     */
    private function sharedMermaidNodeId(array &$nodeIds, string $label, int $rowIndex, int $columnIndex): string
    {
        $key = $columnIndex.':'.$label;

        if (! isset($nodeIds[$key])) {
            $nodeIds[$key] = $this->mermaidNodeId($rowIndex, $columnIndex);
        }

        return $nodeIds[$key];
    }

    private function renderMermaidBlock(string $diagram): string
    {
        if ($diagram === '') {
            return '';
        }

        return '<pre class="mermaid">'.e($diagram).'</pre>';
    }

    /**
     * @param  array<string, int>  $usedHeadingIds
     */
    private function uniqueHeadingId(string $title, array &$usedHeadingIds): string
    {
        $baseId = Str::slug($title) ?: 'section';
        $usedHeadingIds[$baseId] = ($usedHeadingIds[$baseId] ?? 0) + 1;

        if ($usedHeadingIds[$baseId] === 1) {
            return $baseId;
        }

        return "{$baseId}-{$usedHeadingIds[$baseId]}";
    }

    private function cleanHeading(?string $heading): ?string
    {
        $heading = trim((string) $heading);

        return $heading === '' ? null : $heading;
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

    private function truncateMeta(?string $value, int $limit): string
    {
        return Str::of($value ?? '')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit($limit, '')
            ->rtrim(' ,.;:-')
            ->toString();
    }
}

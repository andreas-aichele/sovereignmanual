<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostAsset;
use App\Models\PostBlock;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
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
            'meta' => $this->translationArray('meta', $locale),
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
            ->map(function (PostBlock $block) use (&$usedHeadingIds, &$tableOfContents): array {
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
                        'url' => $block->asset->url,
                        'alt' => $block->asset->alt_text,
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
                'alternate' => $this->alternateUrl($post, $locale),
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
            ],
        ]);
    }

    public function switchLocale(): RedirectResponse
    {
        return redirect()->to($this->localizedRoute(App::currentLocale(), 'index'));
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
            'image' => $coverImage?->url ?? asset('fallback.jpg'),
            'image_alt' => $coverImage?->alt_text ?? $title,
        ];
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

    private function alternateUrl(Post $post, string $locale): ?string
    {
        $alternateLocale = $this->translation('alternate_locale', $locale);
        $translation = $post->translation($alternateLocale);

        if ($translation === null) {
            return null;
        }

        return $this->localizedRoute($alternateLocale, 'show', [
            'category' => $this->categorySlug($post->category, $alternateLocale),
            'slug' => $translation->slug,
        ]);
    }

    /**
     * @return array<int, array{locale: string, label: string, url: string, current: bool}>
     */
    private function languageOptions(string $currentLocale, ?Post $post = null): array
    {
        return collect($this->translationArray('locales', $currentLocale))
            ->map(function (string $label, string $locale) use ($currentLocale, $post): ?array {
                $translation = $post?->translation($locale);

                if ($post !== null && $translation === null) {
                    return null;
                }

                return [
                    'locale' => $locale,
                    'label' => $label,
                    'url' => $translation === null
                        ? $this->localeSwitchUrl($locale)
                        : $this->localizedRoute($locale, 'show', [
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
        return $this->localizedRoute($locale, 'index');
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
        $language = Language::fromLocale($locale) ?? Language::fallback();

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

        if (! in_array($block->type, ['section', 'markdown'], true)) {
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

        $html = $this->renderAsciiDiagramCodeBlocks($html);

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

        return $this->renderAsciiDiagramCodeBlocks($html);
    }

    private function renderAsciiDiagramCodeBlocks(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code\b([^>]*)>(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $code = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (str_contains($matches[1], 'language-mermaid')) {
                    return $this->renderMermaidBlock(trim($code));
                }

                $rows = $this->parseDiagramRows($code);

                if ($rows === []) {
                    return $matches[0];
                }

                return $this->renderMermaidBlock($this->mermaidFlowchart($rows));
            },
            $html
        ) ?? $html;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function renderFlowDiagramData(?array $data): string
    {
        $steps = collect($data['steps'] ?? [])
            ->filter(fn (mixed $step): bool => is_scalar($step) && trim((string) $step) !== '')
            ->map(fn (mixed $step): string => trim((string) $step))
            ->values()
            ->all();

        if ($steps === []) {
            return '';
        }

        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';

        return $this->renderMermaidBlock($this->mermaidFlowchart([$steps], $title));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseDiagramRows(string $code): array
    {
        $lines = Str::of($code)
            ->replace(["\r\n", "\r"], "\n")
            ->explode("\n")
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return [];
        }

        $rows = $lines
            ->map(function (string $line): array {
                if (! preg_match('/(?:-{1,2}>|={1,2}>)/', $line)) {
                    return [];
                }

                return collect(preg_split('/\s*(?:-{1,2}>|={1,2}>)\s*/', $line) ?: [])
                    ->map(fn (string $part): string => $this->cleanDiagramLabel($part))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter(fn (array $row): bool => count($row) >= 2)
            ->values()
            ->all();

        return count($rows) === $lines->count() ? $rows : [];
    }

    private function cleanDiagramLabel(string $label): string
    {
        return Str::of($label)
            ->trim()
            ->replaceMatches('/^\[(.*)\]$/', '$1')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
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
    private function mermaidFlowchart(array $rows, string $title = ''): string
    {
        $lines = ['flowchart LR'];

        if ($title !== '') {
            $lines[] = '%% '.$title;
        }

        foreach ($rows as $rowIndex => $row) {
            for ($columnIndex = 0; $columnIndex < count($row) - 1; $columnIndex++) {
                $fromId = $this->mermaidNodeId($rowIndex, $columnIndex);
                $toId = $this->mermaidNodeId($rowIndex, $columnIndex + 1);
                $fromLabel = $this->mermaidLabel($row[$columnIndex]);
                $toLabel = $this->mermaidLabel($row[$columnIndex + 1]);

                $lines[] = "    {$fromId}[\"{$fromLabel}\"] --> {$toId}[\"{$toLabel}\"]";
            }
        }

        return implode("\n", $lines);
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

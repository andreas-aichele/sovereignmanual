<?php

namespace App\Services;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\Language;
use App\Enums\PostStatus;
use App\Models\AiRun;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use App\Support\ResponsiveImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;
use Laravel\Ai\Providers\Tools\WebSearch;
use RuntimeException;
use Throwable;

use function Laravel\Ai\agent;

class MagazineAiPipeline
{
    public function generatePost(ContentTopic $topic): Post
    {
        if ($this->isNewsTopic($topic) && ! $this->hasCredibleNewsResearch($topic)) {
            $topic->update([
                'status' => ContentTopicStatus::Archived,
            ]);

            Log::channel('queue')->warning('News topic archived because its saved sources no longer pass verification.', [
                'content_topic_id' => $topic->id,
                'content_topic_title' => $topic->title,
            ]);

            throw new RuntimeException('News topics require at least two credible independent sources before article generation.');
        }

        $draftRun = $this->startRun(AiRunType::Draft, $topic);
        $title = $topic->title;
        $internalLinks = $this->internalLinkCandidates('en');
        $draft = $this->promptWithFallback(
            instructions: $this->draftInstructions($topic),
            prompt: $this->draftPrompt($topic, $internalLinks),
            fallback: $this->fallbackMarkdown($title, 'en'),
        );
        $this->finishRun($draftRun, $draft);

        $englishSeo = $this->seoPlan($topic, 'en', $title, $draft, $internalLinks);
        $englishTitle = $englishSeo['article_title'];
        $englishSlug = $this->uniqueTranslationSlug($englishSeo['slug'], 'en');
        $englishExcerpt = $this->excerpt($draft, 180);
        $englishBlocks = $this->articleBlocks($topic, 'en', $englishTitle, $draft, $englishSeo['keywords'], $internalLinks);
        $englishMarkdown = $this->markdownFromBlocks($englishBlocks, $draft);

        $post = Post::create([
            'content_topic_id' => $topic->id,
            'category_id' => $topic->category_id ?? $this->defaultCategory()->id,
            'slug' => $englishSlug,
            'status' => PostStatus::Draft,
            'topic' => $topic->title,
            'audience_level' => $topic->audience_level,
            'primary_language' => $topic->primary_language,
            'scheduled_for' => $topic->scheduled_for,
            'seo' => [
                'keywords' => $englishSeo['keywords'],
                'internal_links' => $internalLinks,
            ],
            'ai_metadata' => [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'auto_generated' => true,
            ],
        ]);

        $post->translations()->create([
            'locale' => 'en',
            'title' => $englishTitle,
            'slug' => $englishSlug,
            'excerpt' => $englishExcerpt,
            'markdown' => $englishMarkdown,
            'meta_title' => $englishSeo['meta_title'],
            'meta_description' => $englishSeo['meta_description'],
            'seo' => [
                'canonical_locale' => 'en',
                'keywords' => $englishSeo['keywords'],
                'internal_links' => $internalLinks,
            ],
        ]);

        $translatedBlocks = [];

        if ($this->shouldTranslateLocale('de')) {
            $translationRun = $this->startRun(AiRunType::Translation, $topic, $post);
            $germanArticle = $this->promptJson(
                instructions: 'Translate Bitcoin magazine articles into precise, natural German. Return only valid JSON. Use real German umlauts and ß. Keep article and section headings compact. Never leave English headings or UI-like labels untranslated unless they are proper nouns.',
                prompt: json_encode([
                    'title' => $englishTitle,
                    'excerpt' => $englishExcerpt,
                    'blocks' => $englishBlocks,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ) ?? $this->fallbackTranslatedArticle($title, $englishBlocks);
            $germanTitle = $this->cleanText((string) ($germanArticle['title'] ?? $this->germanTitle($title)), $this->germanTitle($title));
            $germanBlocks = $this->sanitizeBlocks($germanArticle['blocks'] ?? null, 'de', $germanTitle, $this->fallbackMarkdown($title, 'de'));
            $germanInternalLinks = $this->internalLinkCandidates('de', $post->id);
            $germanDraft = $this->markdownFromBlocks($germanBlocks, $this->fallbackMarkdown($title, 'de'));
            $this->finishRun($translationRun, $germanDraft, ['title' => $germanTitle]);
            $germanSeo = $this->seoPlan($topic, 'de', $germanTitle, $germanDraft, $germanInternalLinks);
            $germanSlug = $this->uniqueTranslationSlug($germanSeo['slug'], 'de');

            $post->translations()->create([
                'locale' => 'de',
                'title' => $germanTitle,
                'slug' => $germanSlug,
                'excerpt' => $this->cleanText((string) ($germanArticle['excerpt'] ?? $this->excerpt($germanDraft, 180)), $this->excerpt($germanDraft, 180)),
                'markdown' => $germanDraft,
                'meta_title' => $germanSeo['meta_title'],
                'meta_description' => $germanSeo['meta_description'],
                'seo' => [
                    'canonical_locale' => 'de',
                    'keywords' => $germanSeo['keywords'],
                    'internal_links' => $germanInternalLinks,
                ],
            ]);

            $translatedBlocks['de'] = $germanBlocks;
        }

        $this->createBlocks($post, 'en', $englishBlocks);

        foreach ($translatedBlocks as $locale => $blocks) {
            $this->createBlocks($post, $locale, $blocks);
        }

        $this->generatePostImage($post, $topic);

        $post->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $topic->update([
            'status' => ContentTopicStatus::Published,
            'last_generated_at' => now(),
        ]);

        return $post;
    }

    /**
     * @return Collection<int, ContentTopic>
     */
    public function createTopicIdeas(int $count = 2): Collection
    {
        $category = $this->randomEvergreenCategory();
        $avoidTopics = $this->recentTopicTitles($category);
        $run = $this->startRun(AiRunType::Topic);
        $response = $this->promptWithFallback(
            instructions: 'You propose focused editorial topics for a Bitcoin-only sovereignty learning portal. Return one topic per line. Make each idea specific, practical, and clearly different from the avoided existing topics.',
            prompt: json_encode([
                'task' => "Create {$count} evergreen article ideas for this category.",
                'category' => [
                    'key' => $category->key,
                    'name' => $category->name,
                    'description' => strip_tags((string) Str::markdown($category->description)),
                ],
                'audience' => 'Bitcoin beginners and intermediate users moving toward independent custody.',
                'avoid' => [
                    'altcoins',
                    'trading',
                    'price predictions',
                    'financial advice',
                    'generic sovereignty psychology angles',
                    ...$avoidTopics,
                ],
                'style' => [
                    'concrete examples',
                    'category-specific angle',
                    'less AI-polished phrasing',
                    'clear practical reader outcome',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            fallback: implode("\n", $this->fallbackTopicTitles($category)),
        );

        $topics = Str::of($response)
            ->explode("\n")
            ->map(fn (string $line): string => trim(preg_replace('/^[\-\*\d\.\)\s]+/', '', $line) ?? ''))
            ->filter()
            ->take($count)
            ->values()
            ->map(fn (string $title, int $index): ContentTopic => ContentTopic::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'category_id' => $category->id,
                    'status' => ContentTopicStatus::Scheduled,
                    'priority' => max(1, 10 - $index),
                    'audience_level' => 'intermediate',
                    'primary_language' => 'en',
                    'target_languages' => ['de'],
                    'scheduled_for' => now(),
                    'brief' => 'AI-proposed topic for Sovereign Manual with practical, non-hype educational framing.',
                    'constraints' => [
                        'tone' => 'clear, practical, non-hype',
                        'brand' => 'synthwave-cypherpunk editorial',
                        'category_key' => $category->key,
                        'avoid_similar_topics' => $avoidTopics,
                    ],
                ],
            ));

        $this->finishRun($run, $response, [
            'created_topics' => $topics->pluck('id')->all(),
            'category_key' => $category->key,
            'avoid_similar_topics' => $avoidTopics,
        ]);

        return $topics;
    }

    /**
     * @return Collection<int, ContentTopic>
     */
    public function createNewsTopicIdeas(int $count = 1): Collection
    {
        $run = $this->startRun(AiRunType::Topic);

        if (! $this->hasAiProviderKey()) {
            $message = 'News topic ideation skipped because the configured AI provider has no key for live web research.';
            Log::channel('queue')->warning($message, [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
            ]);
            $this->finishRun($run, $message, ['created_topics' => [], 'reason' => 'missing_ai_provider_key']);

            return collect();
        }

        $newsCategory = $this->newsCategory();
        $avoidTopics = $this->recentTopicTitles($newsCategory);
        $research = null;
        $topics = collect();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $research = $this->promptGroundedJson(
                instructions: 'Research current Bitcoin-only news using live Google Search grounding. Return only valid JSON. Exclude altcoins, trading, price predictions, and hype. Prefer primary sources, official publications, reputable mainstream financial/technology reporting, respected Bitcoin technical sources, and government or court records. Treat social posts, anonymous blogs, exchanges, and aggregators as supporting context only. Use direct canonical source URLs, not Google Search or Vertex grounding redirect URLs.',
                prompt: json_encode([
                    'task' => 'Find current Bitcoin news topic candidates suitable for Sovereign Manual.',
                    'current_date' => now()->toDateString(),
                    'attempt' => $attempt,
                    'candidate_count' => max(3, $count * 3),
                    'credibility_standard' => 'Each topic must have at least two independent credible sources. Prefer primary sources. Include publication dates, direct source urls, source types, credibility notes, and unresolved uncertainties.',
                    'important' => 'Return candidate topics discovered through search even when some sources are weak; classify weak sources as supporting. Do not return an empty topics array unless no Bitcoin-only candidate can be found at all. The application will reject candidates that do not meet the final credibility threshold.',
                    'avoid_similar_topics' => $avoidTopics,
                    'schema' => [
                        'topics' => [
                            [
                                'title' => 'Specific Bitcoin news topic',
                                'summary' => 'Short factual brief with context and why it matters',
                                'sources' => [
                                    [
                                        'title' => 'Source title',
                                        'url' => 'https://example.com/source',
                                        'published_at' => 'YYYY-MM-DD',
                                        'publisher' => 'Publisher',
                                        'type' => 'primary|reputable_reporting|technical|official|supporting',
                                        'credibility_note' => 'Why this source is credible',
                                    ],
                                ],
                                'credibility_notes' => ['Independent confirmation details'],
                                'open_questions' => ['Known uncertainty'],
                            ],
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                tools: [
                    new WebSearch(maxSearches: 8),
                ],
            );

            if ($research === null) {
                continue;
            }

            $topics = $this->createNewsTopicsFromResearch($research, $newsCategory, $count, $avoidTopics);

            if ($topics->isNotEmpty()) {
                break;
            }

            Log::channel('queue')->warning('News topic ideation attempt did not meet credibility threshold.', [
                'attempt' => $attempt,
                ...$this->newsResearchDiagnostics($research),
            ]);
        }

        if ($research === null) {
            $this->failRun($run, 'News topic ideation failed because the AI provider did not return valid structured research.', [
                'category_key' => $newsCategory->key,
            ]);

            throw new RuntimeException('News topic ideation failed because the AI provider did not return valid structured research.');
        }

        if ($topics->isEmpty()) {
            $this->failRun($run, 'News topic ideation found no topics with at least two credible independent sources.', [
                'category_key' => $newsCategory->key,
                ...$this->newsResearchDiagnostics($research),
            ]);

            throw new RuntimeException('News topic ideation found no topics with at least two credible independent sources.');
        }

        $this->finishRun($run, json_encode($research ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), [
            'created_topics' => $topics->pluck('id')->all(),
            'category_key' => $newsCategory->key,
            'accepted_topics' => $topics->count(),
        ]);

        return $topics;
    }

    public function regeneratePostImage(Post $post): void
    {
        $post->assets()
            ->where('type', 'image')
            ->get()
            ->filter(fn ($asset): bool => str_contains((string) $asset->url, 'unsplash')
                || ($asset->metadata['style'] ?? null) !== 'synthwave-cypherpunk')
            ->each(fn ($asset): bool => $asset->update(['status' => 'replaced']));

        $this->generatePostImage(
            $post,
            $post->contentTopic ?? new ContentTopic([
                'title' => $post->topic,
                'audience_level' => $post->audience_level,
                'primary_language' => $post->primary_language,
            ]),
        );
    }

    private function promptWithFallback(string $instructions, string $prompt, string $fallback): string
    {
        if (! $this->hasAiProviderKey()) {
            return $fallback;
        }

        try {
            return (string) agent($instructions)
                ->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('Magazine AI text prompt failed; using fallback response.', [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function promptJson(string $instructions, string $prompt): ?array
    {
        if (! $this->hasAiProviderKey()) {
            return null;
        }

        try {
            $response = (string) agent($instructions)
                ->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));

            $json = $this->extractJsonPayload($response);

            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('Magazine AI JSON prompt failed.', [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function promptGroundedJson(string $instructions, string $prompt, array $tools = []): ?array
    {
        if (! $this->hasAiProviderKey()) {
            return null;
        }

        try {
            $response = agent(
                instructions: $instructions,
                tools: $tools,
            )->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));

            $decoded = json_decode($this->extractJsonPayload((string) $response), true, 512, JSON_THROW_ON_ERROR);
            $citations = $response->meta->citations
                ->map(fn ($citation): array => [
                    'title' => $citation->title,
                    'url' => $citation->url,
                ])
                ->values()
                ->all();

            return is_array($decoded)
                ? $decoded + ['grounding_citations' => $citations]
                : null;
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('Magazine AI grounded JSON prompt failed.', [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function promptStructuredJson(string $instructions, string $prompt, array $tools = []): ?array
    {
        if (! $this->hasAiProviderKey()) {
            return null;
        }

        try {
            $response = agent(
                instructions: $instructions,
                tools: $tools,
                schema: fn ($schema): array => [
                    'topics' => $schema->array()
                        ->items($schema->object([
                            'title' => $schema->string()->required(),
                            'summary' => $schema->string()->required(),
                            'sources' => $schema->array()
                                ->items($schema->object([
                                    'title' => $schema->string()->required(),
                                    'url' => $schema->string()->format('uri')->required(),
                                    'published_at' => $schema->string()->required(),
                                    'publisher' => $schema->string()->required(),
                                    'type' => $schema->string()->enum(['primary', 'reputable_reporting', 'technical', 'official', 'supporting'])->required(),
                                    'credibility_note' => $schema->string()->required(),
                                ])->withoutAdditionalProperties())
                                ->min(2)
                                ->required(),
                            'credibility_notes' => $schema->array()->items($schema->string())->required(),
                            'open_questions' => $schema->array()->items($schema->string())->required(),
                        ])->withoutAdditionalProperties())
                        ->required(),
                ],
            )->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));

            $structured = $response->toArray();
            $citations = $response->meta->citations
                ->map(fn ($citation): array => [
                    'title' => $citation->title,
                    'url' => $citation->url,
                ])
                ->values()
                ->all();

            return is_array($structured)
                ? $structured + ['grounding_citations' => $citations]
                : null;
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('Magazine AI structured JSON prompt failed.', [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function extractJsonPayload(string $response): string
    {
        $json = Str::of($response)
            ->replaceMatches('/^```(?:json)?\s*/', '')
            ->replaceMatches('/\s*```$/', '')
            ->trim()
            ->toString();

        if (str_starts_with($json, '{') || str_starts_with($json, '[')) {
            return $json;
        }

        $objectStart = mb_strpos($json, '{');
        $objectEnd = mb_strrpos($json, '}');

        if ($objectStart !== false && $objectEnd !== false && $objectEnd > $objectStart) {
            return mb_substr($json, $objectStart, $objectEnd - $objectStart + 1);
        }

        $arrayStart = mb_strpos($json, '[');
        $arrayEnd = mb_strrpos($json, ']');

        if ($arrayStart !== false && $arrayEnd !== false && $arrayEnd > $arrayStart) {
            return mb_substr($json, $arrayStart, $arrayEnd - $arrayStart + 1);
        }

        return $json;
    }

    private function hasAiProviderKey(): bool
    {
        return filled(config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key'));
    }

    private function shouldTranslateLocale(string $locale): bool
    {
        return $locale !== 'en' && Locales::isSupported($locale);
    }

    private function draftInstructions(ContentTopic $topic): string
    {
        if ($this->isNewsTopic($topic)) {
            return 'You write sourced Bitcoin-only news analysis in clear English Markdown. Lead with verified facts, explain why the development matters, distinguish fact from uncertainty, cite the provided sources naturally, and avoid hype, trading framing, price predictions, altcoins, and financial advice.';
        }

        return 'You write educational, non-hype Bitcoin and financial independence articles in clear English Markdown. Use concise article and section headings. Build a clear SEO-friendly structure with short paragraphs, H2/H3 headings, useful lists, concrete examples, category-specific reader outcomes, and naturally repeated keywords. Do not keyword-stuff. Avoid generic sovereignty psychology phrasing unless the topic specifically requires it.';
    }

    /**
     * @param  array<int, array{title: string, url: string, slug: string}>  $internalLinks
     */
    private function draftPrompt(ContentTopic $topic, array $internalLinks): string
    {
        return json_encode([
            'task' => "Write a practical article for {$topic->audience_level} readers.",
            'category' => $topic->category?->label('en') ?? $topic->categorySlug(),
            'topic' => $topic->title,
            'brief' => $topic->brief,
            'research' => $this->isNewsTopic($topic) ? ($topic->constraints['news_research'] ?? null) : null,
            'internal_link_candidates' => $internalLinks,
            'quality_bar' => [
                'write like a careful editor, not a generic AI assistant',
                'include concrete examples and practical implications',
                'avoid repeated wording from recent articles',
                'do not add a related reading section',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<int, string>  $keywords
     * @param  array<int, array{title: string, url: string, slug: string}>  $internalLinks
     * @return array<int, array<string, mixed>>
     */
    private function articleBlocks(ContentTopic $topic, string $locale, string $title, string $markdown, array $keywords = [], array $internalLinks = []): array
    {
        $fallback = [
            'blocks' => $this->fallbackBlocks($title, $locale, $markdown),
        ];

        $response = $this->promptJson(
            instructions: 'Convert an educational Bitcoin article into a premium magazine block plan. Return only valid JSON. Preserve the full article detail. Do not summarize, shorten, or omit practical examples. Split the full draft into section blocks with several paragraphs each. Keep every section heading compact, ideally 3 to 7 words. Naturally use the provided SEO keywords in headings and body text where they fit. Add relevant internal Markdown links from the provided candidates. Use these block types only: section, insight, checklist, flow_diagram, sketch. Use section blocks for article sections with a heading, anchor, and markdown body that does not repeat the heading. Visual blocks may supplement the article, but must not replace section text. Do not include raw HTML or raw SVG.',
            prompt: json_encode([
                'locale' => $locale,
                'topic' => $topic->title,
                'audience_level' => $topic->audience_level,
                'brief' => $topic->brief,
                'seo_keywords' => $keywords,
                'internal_link_candidates' => $internalLinks,
                'markdown' => $markdown,
                'schema' => [
                    'blocks' => [
                        [
                            'type' => 'section',
                            'heading' => 'Section heading',
                            'anchor' => 'section-heading',
                            'markdown' => 'Markdown section body without the heading',
                            'data' => [],
                        ],
                        [
                            'type' => 'insight',
                            'markdown' => null,
                            'data' => ['title' => 'Short label', 'body' => 'One focused insight'],
                        ],
                        [
                            'type' => 'checklist',
                            'markdown' => null,
                            'data' => ['title' => 'Checklist title', 'items' => ['Action one', 'Action two']],
                        ],
                        [
                            'type' => 'flow_diagram',
                            'markdown' => null,
                            'data' => ['title' => 'Flow title', 'steps' => ['Step one', 'Step two', 'Step three']],
                        ],
                        [
                            'type' => 'sketch',
                            'markdown' => null,
                            'data' => ['title' => 'Sketch title', 'caption' => 'Short caption', 'labels' => ['Label one', 'Label two']],
                        ],
                    ],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ) ?? $fallback;

        return $this->sanitizeBlocks($response['blocks'] ?? null, $locale, $title, $markdown);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeBlocks(mixed $blocks, string $locale, string $title, string $markdown): array
    {
        if (! is_array($blocks)) {
            return $this->fallbackBlocks($title, $locale, $markdown);
        }

        $allowedTypes = ['section', 'markdown', 'insight', 'checklist', 'flow_diagram', 'sketch'];
        $sanitized = collect($blocks)
            ->filter(fn (mixed $block): bool => is_array($block))
            ->map(function (array $block) use ($allowedTypes, $locale): array {
                $type = in_array($block['type'] ?? null, $allowedTypes, true) ? $block['type'] : 'markdown';
                $data = is_array($block['data'] ?? null) ? $this->sanitizeBlockData($block['data']) : [];
                $heading = $type === 'section'
                    ? $this->seoText((string) ($block['heading'] ?? ''), $locale === 'de' ? 'Praxis' : 'Practice', 72)
                    : null;
                $anchor = filled($heading) ? Str::slug((string) ($block['anchor'] ?? $heading)) : null;

                return [
                    'type' => $type,
                    'heading' => filled($heading) ? $heading : null,
                    'anchor' => filled($anchor) ? $anchor : null,
                    'markdown' => in_array($type, ['section', 'markdown'], true) ? $this->cleanMarkdown($block['markdown'] ?? null) : null,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $block): bool => ! in_array($block['type'], ['section', 'markdown'], true) || filled($block['markdown']))
            ->take(12)
            ->values()
            ->all();

        if ($sanitized === []) {
            return $this->fallbackBlocks($title, $locale, $markdown);
        }

        if (! collect($sanitized)->contains(fn (array $block): bool => in_array($block['type'], ['insight', 'checklist', 'flow_diagram', 'sketch'], true))) {
            $sanitized[] = $this->fallbackVisualBlock($locale);
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeBlockData(array $data): array
    {
        return collect($data)
            ->map(function (mixed $value): mixed {
                if (is_array($value)) {
                    return collect($value)
                        ->filter(fn (mixed $item): bool => is_scalar($item))
                        ->map(fn (mixed $item): string => $this->cleanText((string) $item, ''))
                        ->filter()
                        ->take(6)
                        ->values()
                        ->all();
                }

                return is_scalar($value) ? $this->cleanText((string) $value, '') : null;
            })
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function markdownFromBlocks(array $blocks, string $fallback): string
    {
        $markdown = collect($blocks)
            ->map(function (array $block): ?string {
                if (! filled($block['markdown'] ?? null)) {
                    return null;
                }

                if (($block['type'] ?? null) === 'section' && filled($block['heading'] ?? null)) {
                    return "## {$block['heading']}\n\n{$block['markdown']}";
                }

                return $block['markdown'];
            })
            ->filter(fn (?string $markdown): bool => filled($markdown))
            ->implode("\n\n");

        return filled($markdown) ? $markdown : $fallback;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function createBlocks(Post $post, string $locale, array $blocks): void
    {
        foreach ($blocks as $index => $block) {
            $post->blocks()->create([
                'locale' => $locale,
                'type' => $block['type'],
                'sort_order' => $index,
                'heading' => $block['heading'] ?? null,
                'anchor' => $block['anchor'] ?? null,
                'markdown' => $block['markdown'] ?? null,
                'data' => $block['data'] ?? [],
            ]);
        }
    }

    private function generatePostImage(Post $post, ContentTopic $topic): void
    {
        $prompt = $this->synthwaveImagePrompt($topic);
        $run = $this->startRun(AiRunType::Image, $topic, $post, config('magazine_ai.image_model', 'gemini-2.5-flash-image'));
        $metadata = [
            'style' => 'synthwave-cypherpunk',
            'role' => 'header',
            'prompt_version' => 2,
            'no_unsplash' => true,
        ];

        if (! config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key')) {
            $post->assets()->create([
                'type' => 'image',
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty background for {$topic->title}",
                'status' => 'pending',
                'metadata' => $metadata + ['reason' => 'image_generation_not_configured'],
            ]);

            $this->finishRun($run, 'Image generation skipped because the configured AI provider has no key.', $metadata);

            return;
        }

        try {
            $image = Image::of($prompt)
                ->landscape()
                ->quality('medium')
                ->generate(provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.image_model', 'gemini-2.5-flash-image'));

            $temporaryPath = $image->storePubliclyAs("post-assets/{$post->id}", "{$post->slug}.png", 'public');
            $path = is_string($temporaryPath)
                ? app(ResponsiveImage::class)->convertToJpeg('public', $temporaryPath, "post-assets/{$post->id}/{$post->slug}.jpg") ?? $temporaryPath
                : null;

            if (is_string($temporaryPath) && $path !== $temporaryPath) {
                Storage::disk('public')->delete($temporaryPath);
            }

            $responsiveImage = is_string($path)
                ? app(ResponsiveImage::class)->generate('public', $path)
                : null;

            $post->assets()->create([
                'type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'url' => is_string($path) ? Storage::disk('public')->url($path) : null,
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty background for {$topic->title}",
                'status' => is_string($path) ? 'ready' : 'pending',
                'metadata' => $responsiveImage === null ? $metadata : $metadata + ['responsive_image' => $responsiveImage],
            ]);

            $this->finishRun($run, 'Image generated and stored.', $metadata);
        } catch (Throwable $exception) {
            Log::channel('queue')->error('Magazine AI image generation failed; using pending asset placeholder.', [
                'post_id' => $post->id,
                'content_topic_id' => $topic->id,
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            $post->assets()->create([
                'type' => 'image',
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty background for {$topic->title}",
                'status' => 'pending',
                'metadata' => $metadata + ['error' => $exception->getMessage()],
            ]);

            $this->finishRun($run, $exception->getMessage(), $metadata + ['failed' => true]);
        }
    }

    private function synthwaveImagePrompt(ContentTopic $topic): string
    {
        return "Full-bleed synthwave editorial website background for article topic: {$topic->title}. Audience level: {$topic->audience_level}. Bitcoin financial sovereignty context, dark readable magazine atmosphere, Bitcoin orange focal light, restrained neon cyan and magenta accents, subtle grid lines, abstract ledger details, human-scale personal scene details like warm desk light, hands, notes, or a non-identifiable silhouette, modern handcrafted editorial character, cinematic depth, natural imperfections. Edge-to-edge background art only; no border, no frame, no book, no magazine mockup, no poster, no device mockup, no page layout, no floating card, no text in image, no logos, no identifiable real people, no stock-photo look, no glossy AI-slop aesthetic.";
    }

    /**
     * @param  array<int, array{title: string, url: string, slug: string}>  $internalLinks
     * @return array{article_title: string, meta_title: string, meta_description: string, slug: string, keywords: array<int, string>}
     */
    private function seoPlan(ContentTopic $topic, string $locale, string $title, string $markdown, array $internalLinks): array
    {
        $fallback = $this->fallbackSeoPlan($topic, $locale, $title, $markdown);

        if (! $this->hasAiProviderKey()) {
            return $fallback;
        }

        $feedback = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->promptJson(
                instructions: 'Create SEO metadata for an educational Bitcoin magazine article. Return only valid JSON. Generate the visible H1 article_title and the browser/search meta_title directly at the correct length. They may differ slightly. Do not return an overlong title for PHP to shorten later. Keep article_title readable and specific, up to 70 characters. Keep meta_title compelling and specific, up to 60 characters. Keep meta_description up to 160 characters. Avoid hype. Identify relevant keywords that should appear naturally in headings, body copy, and meta tags.',
                prompt: json_encode([
                    'locale' => $locale,
                    'topic' => $topic->title,
                    'brief' => $topic->brief,
                    'article_title_candidate' => $title,
                    'markdown' => $this->excerpt($markdown, 1200),
                    'internal_link_candidates' => $internalLinks,
                    'previous_attempt_feedback' => $feedback,
                    'limits' => [
                        'article_title' => 70,
                        'meta_title' => 60,
                        'meta_description' => 160,
                        'slug_words' => 6,
                        'keywords' => 8,
                    ],
                    'schema' => [
                        'article_title' => 'Natural visible H1 title up to 70 characters',
                        'meta_title' => 'Natural SEO title up to 60 characters, may differ from H1',
                        'meta_description' => 'SEO description up to 160 characters',
                        'slug' => 'short-hyphenated-url-slug',
                        'keywords' => ['keyword one', 'keyword two'],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );

            if ($response === null) {
                $feedback = 'Return valid JSON matching the requested schema.';

                continue;
            }

            $keywords = collect($response['keywords'] ?? $fallback['keywords'])
                ->filter(fn (mixed $keyword): bool => is_scalar($keyword))
                ->map(fn (mixed $keyword): string => Str::of((string) $keyword)->lower()->replaceMatches('/\s+/', ' ')->trim()->limit(48, '')->toString())
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all();

            $plan = [
                'article_title' => $this->cleanText((string) ($response['article_title'] ?? ''), ''),
                'meta_title' => $this->cleanText((string) ($response['meta_title'] ?? ''), ''),
                'meta_description' => $this->cleanText((string) ($response['meta_description'] ?? ''), ''),
                'slug' => $this->shortSlug((string) ($response['slug'] ?? $fallback['slug']), $locale),
                'keywords' => $keywords === [] ? $fallback['keywords'] : $keywords,
            ];

            $problems = [];

            if ($plan['article_title'] === '' || mb_strlen($plan['article_title']) > 70) {
                $problems[] = 'article_title must be present and at most 70 characters';
            }

            if ($plan['meta_title'] === '' || mb_strlen($plan['meta_title']) > 60) {
                $problems[] = 'meta_title must be present and at most 60 characters';
            }

            if ($plan['meta_description'] === '' || mb_strlen($plan['meta_description']) > 160) {
                $problems[] = 'meta_description must be present and at most 160 characters';
            }

            $feedback = implode('; ', $problems);

            if ($feedback === '') {
                return $plan;
            }
        }

        Log::channel('queue')->warning('Magazine AI SEO metadata did not satisfy length constraints; using fallback response.', [
            'content_topic_id' => $topic->id,
            'locale' => $locale,
            'feedback' => $feedback,
        ]);

        return $fallback;
    }

    /**
     * @return array{article_title: string, meta_title: string, meta_description: string, slug: string, keywords: array<int, string>}
     */
    private function fallbackSeoPlan(ContentTopic $topic, string $locale, string $title, string $markdown): array
    {
        $articleTitle = $this->seoText($title, $locale === 'de' ? 'Bitcoin-Strategie' : 'Bitcoin strategy', 70);
        $keywords = $this->keywordsFor($topic, $locale);

        return [
            'article_title' => $articleTitle,
            'meta_title' => $this->seoText($articleTitle, $articleTitle, 60),
            'meta_description' => $this->seoText($this->excerpt($markdown, 160), $locale === 'de'
                ? 'Praxisnaher Leitfaden zu Bitcoin, Selbstverwahrung und finanzieller Souveränität.'
                : 'A practical guide to Bitcoin, self custody, and financial sovereignty.', 160),
            'slug' => $this->shortSlug($articleTitle, $locale),
            'keywords' => $keywords,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keywordsFor(ContentTopic $topic, string $locale): array
    {
        $base = $locale === 'de'
            ? ['bitcoin', 'selbstverwahrung', 'finanzielle souveränität']
            : ['bitcoin', 'self custody', 'financial sovereignty'];

        return collect([
            ...$base,
            $topic->categorySlug(),
            ...Str::of($topic->title.' '.$topic->brief)
                ->lower()
                ->replaceMatches('/[^\pL\pN\s-]+/u', ' ')
                ->explode(' ')
                ->filter(fn (string $word): bool => mb_strlen($word) > 4)
                ->take(5)
                ->all(),
        ])
            ->map(fn (?string $keyword): string => Str::of((string) $keyword)->replace('-', ' ')->replaceMatches('/\s+/', ' ')->trim()->toString())
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{title: string, url: string, slug: string}>
     */
    private function internalLinkCandidates(string $locale, ?int $excludePostId = null): array
    {
        return Post::query()
            ->with(['category', 'translations'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($excludePostId !== null, fn ($query) => $query->whereKeyNot($excludePostId))
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(function (Post $post) use ($locale): ?array {
                $translation = $post->translation($locale);

                if ($translation === null) {
                    return null;
                }

                return [
                    'title' => $translation->title,
                    'url' => $this->localizedRoute($locale, 'show', [
                        'category' => $post->category?->localizedSlug($locale) ?? 'self-custody',
                        'slug' => $translation->slug,
                    ]),
                    'slug' => $translation->slug,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function defaultCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['key' => 'self-custody', 'lang' => Language::English],
            [
                'slug' => 'self-custody',
                'name' => 'Self Custody',
                'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
            ]
        );
    }

    private function randomEvergreenCategory(): Category
    {
        return Category::query()
            ->where('lang', Language::English)
            ->where('key', '!=', 'news')
            ->inRandomOrder()
            ->first()
            ?? $this->defaultCategory();
    }

    private function newsCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['key' => 'news', 'lang' => Language::English],
            [
                'slug' => 'news',
                'name' => 'News',
                'description' => 'Current Bitcoin developments explained with context and credible sources.',
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    private function fallbackTopicTitles(Category $category): array
    {
        return match ($category->key) {
            'privacy-security' => [
                'How to build a simple Bitcoin privacy threat model',
                'Everyday security habits for Bitcoin wallet users',
            ],
            'financial-sovereignty' => [
                'How to write a personal Bitcoin treasury policy',
                'Why savings rules matter more than market predictions',
            ],
            'family-legacy' => [
                'How to prepare family members for Bitcoin recovery',
                'A practical Bitcoin inheritance documentation checklist',
            ],
            'tools-practice' => [
                'How to test a Bitcoin wallet backup safely',
                'A practical checklist for your first Bitcoin node',
            ],
            'economics' => [
                'What monetary scarcity means for everyday savers',
                'How inflation changes long-term savings behavior',
            ],
            'mindset' => [
                'How low time preference changes Bitcoin decisions',
                'Why simple Bitcoin rules reduce emotional mistakes',
            ],
            default => [
                'Bitcoin self custody threat models for beginners',
                'How to avoid common wallet backup mistakes',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private function recentTopicTitles(Category $category, int $limit = 12): array
    {
        $postTitles = Post::query()
            ->with('translations')
            ->whereBelongsTo($category)
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->flatMap(fn (Post $post): array => [
                $post->topic,
                ...$post->translations->pluck('title')->all(),
            ]);

        $topicTitles = ContentTopic::query()
            ->whereBelongsTo($category)
            ->latest()
            ->limit($limit)
            ->pluck('title');

        return $postTitles
            ->merge($topicTitles)
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
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

    private function isNewsTopic(ContentTopic $topic): bool
    {
        return $topic->categorySlug() === 'news';
    }

    private function hasCredibleNewsResearch(ContentTopic $topic): bool
    {
        $research = $topic->constraints['news_research'] ?? null;

        if (! is_array($research)) {
            return false;
        }

        return $this->hasCredibleSourceSet($research['sources'] ?? []);
    }

    /**
     * @return Collection<int, ContentTopic>
     */
    private function createNewsTopicsFromResearch(?array $research, Category $category, int $count, array $avoidTopics): Collection
    {
        $groundingCitations = $this->groundingCitations($research['grounding_citations'] ?? []);

        $topics = collect($research['topics'] ?? [])
            ->filter(fn (mixed $topic): bool => is_array($topic))
            ->filter(fn (array $topic): bool => filled($topic['title'] ?? null))
            ->map(function (array $topic): array {
                $topic['credible_sources'] = $this->credibleSources($topic['sources'] ?? [])->values()->all();

                return $topic;
            })
            ->filter(fn (array $topic): bool => $this->credibleSourceSetPasses(collect($topic['credible_sources'])))
            ->take($count)
            ->values()
            ->map(function (array $topic, int $index) use ($category, $avoidTopics, $groundingCitations): ContentTopic {
                $title = $this->cleanText((string) $topic['title'], 'Bitcoin news update');
                $sources = $topic['credible_sources'];
                $credibilityNotes = $this->stringList($topic['credibility_notes'] ?? []);
                $openQuestions = $this->stringList($topic['open_questions'] ?? []);
                $brief = $this->cleanText((string) ($topic['summary'] ?? ''), 'Current Bitcoin news item requiring sourced context.');

                return ContentTopic::firstOrCreate(
                    ['slug' => Str::slug($title)],
                    [
                        'title' => $title,
                        'category_id' => $category->id,
                        'status' => ContentTopicStatus::Scheduled,
                        'priority' => max(1, 10 - $index),
                        'audience_level' => 'intermediate',
                        'primary_language' => 'en',
                        'target_languages' => ['de'],
                        'scheduled_for' => now(),
                        'brief' => $brief,
                        'constraints' => [
                            'tone' => 'clear, sourced, non-hype',
                            'brand' => 'synthwave-cypherpunk editorial',
                            'category_key' => 'news',
                            'avoid_similar_topics' => $avoidTopics,
                            'news_research' => [
                                'summary' => $brief,
                                'sources' => $sources,
                                'grounding_citations' => $groundingCitations,
                                'credibility_notes' => $credibilityNotes,
                                'open_questions' => $openQuestions,
                                'researched_at' => now()->toAtomString(),
                            ],
                        ],
                    ],
                );
            });

        if ($topics->isEmpty()) {
            Log::channel('queue')->warning('News topic ideation produced no topics that met the credibility threshold.', [
                'required_credible_sources' => 2,
                'candidate_topics' => is_countable($research['topics'] ?? null) ? count($research['topics']) : 0,
            ]);
        }

        return $topics;
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function credibleSources(mixed $sources): Collection
    {
        if (! is_array($sources)) {
            return collect();
        }

        return collect($sources)
            ->filter(fn (mixed $source): bool => is_array($source))
            ->map(function (array $source): array {
                return [
                    'title' => $this->cleanText((string) ($source['title'] ?? ''), ''),
                    'url' => $this->cleanText((string) ($source['url'] ?? ''), ''),
                    'published_at' => $this->cleanText((string) ($source['published_at'] ?? ''), ''),
                    'publisher' => $this->cleanText((string) ($source['publisher'] ?? ''), ''),
                    'type' => Str::of((string) ($source['type'] ?? ''))->lower()->trim()->toString(),
                    'credibility_note' => $this->cleanText((string) ($source['credibility_note'] ?? ''), ''),
                ];
            })
            ->filter(fn (array $source): bool => filter_var($source['url'], FILTER_VALIDATE_URL) !== false)
            ->filter(fn (array $source): bool => str_starts_with($source['url'], 'https://'))
            ->filter(fn (array $source): bool => in_array($source['type'], ['primary', 'reputable_reporting', 'technical', 'official'], true))
            ->filter(fn (array $source): bool => $this->sourceUrlIsReachable($source['url']))
            ->unique(fn (array $source): string => $this->urlHost($source['url']) ?? $this->normalizedUrl($source['url']))
            ->values();
    }

    private function hasCredibleSourceSet(mixed $sources): bool
    {
        return $this->credibleSourceSetPasses($this->credibleSources($sources));
    }

    /**
     * @param  Collection<int, array<string, string>>  $credibleSources
     */
    private function credibleSourceSetPasses(Collection $credibleSources): bool
    {
        return $credibleSources->count() >= 2
            && $credibleSources->contains(fn (array $source): bool => in_array($source['type'], ['primary', 'technical', 'official'], true));
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    private function groundingCitations(mixed $citations): array
    {
        if (! is_array($citations)) {
            return [];
        }

        return collect($citations)
            ->filter(fn (mixed $citation): bool => is_array($citation))
            ->map(fn (array $citation): array => [
                'title' => $this->cleanText((string) ($citation['title'] ?? ''), ''),
                'url' => $this->cleanText((string) ($citation['url'] ?? ''), ''),
            ])
            ->filter(fn (array $citation): bool => filter_var($citation['url'], FILTER_VALIDATE_URL) !== false)
            ->filter(fn (array $citation): bool => str_starts_with($citation['url'], 'https://'))
            ->unique(fn (array $citation): string => $this->normalizedUrl($citation['url']))
            ->values()
            ->all();
    }

    private function normalizedUrl(string $url): string
    {
        return Str::of($url)
            ->lower()
            ->replaceMatches('/#.*$/', '')
            ->rtrim('/')
            ->toString();
    }

    private function urlHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return Str::of($host)
            ->lower()
            ->replaceMatches('/^www\./', '')
            ->toString();
    }

    private function domainFromText(string $value): ?string
    {
        if (preg_match('/(?:[a-z0-9-]+\.)+[a-z]{2,}/i', $value, $matches) !== 1) {
            return null;
        }

        return Str::of($matches[0])
            ->lower()
            ->replaceMatches('/^www\./', '')
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    private function newsResearchDiagnostics(array $research): array
    {
        $topics = collect($research['topics'] ?? [])->filter(fn (mixed $topic): bool => is_array($topic));
        $citations = $this->groundingCitations($research['grounding_citations'] ?? []);

        return [
            'candidate_topics' => $topics->count(),
            'candidate_titles' => $topics
                ->pluck('title')
                ->filter()
                ->take(5)
                ->values()
                ->all(),
            'candidate_source_hosts' => $topics
                ->flatMap(fn (array $topic): array => is_array($topic['sources'] ?? null) ? $topic['sources'] : [])
                ->filter(fn (mixed $source): bool => is_array($source) && filled($source['url'] ?? null))
                ->map(fn (array $source): ?string => $this->urlHost((string) $source['url']))
                ->filter()
                ->unique()
                ->take(10)
                ->values()
                ->all(),
            'grounding_citations' => count($citations),
            'grounding_domains' => collect($citations)
                ->map(fn (array $citation): ?string => $this->domainFromText($citation['title']) ?? $this->urlHost($citation['url']))
                ->filter()
                ->unique()
                ->take(10)
                ->values()
                ->all(),
        ];
    }

    private function sourceUrlIsReachable(string $url): bool
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->head($url);

            if ($response->successful()) {
                return true;
            }

            if (! in_array($response->status(), [403, 405], true)) {
                return false;
            }

            return Http::timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->get($url)
                ->successful();
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('News source URL verification failed.', [
                'url' => $url,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item): bool => is_scalar($item))
            ->map(fn (mixed $item): string => $this->cleanText((string) $item, ''))
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }

    private function uniqueTranslationSlug(string $slug, string $locale): string
    {
        $baseSlug = $this->shortSlug($slug, $locale);
        $slug = $baseSlug;
        $suffix = 2;

        while (Post::query()->where('slug', $slug)->exists()
            || PostTranslation::query()->where('locale', $locale)->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function seoText(string $value, string $fallback, int $limit): string
    {
        $cleaned = $this->cleanText($value, $fallback);

        return Str::of($cleaned)
            ->limit($limit, '', true)
            ->rtrim(' ,.;:-')
            ->toString();
    }

    private function shortSlug(string $value, string $locale): string
    {
        $slug = Str::slug($value, '-', $locale);
        $words = collect(explode('-', $slug))
            ->filter()
            ->take(6)
            ->implode('-');

        return filled($words) ? $words : ($locale === 'de' ? 'bitcoin-strategie' : 'bitcoin-strategy');
    }

    private function startRun(AiRunType $type, ?ContentTopic $topic = null, ?Post $post = null, ?string $model = null): AiRun
    {
        $run = AiRun::create([
            'content_topic_id' => $topic?->id,
            'post_id' => $post?->id,
            'type' => $type,
            'status' => AiRunStatus::Running,
            'provider' => config('magazine_ai.provider', 'gemini'),
            'model' => $model ?? config('magazine_ai.text_model', 'gemini-2.5-flash'),
            'started_at' => now(),
        ]);

        Log::channel('queue')->info('Magazine AI run started.', $this->aiRunLogContext($run));

        return $run;
    }

    private function finishRun(AiRun $run, string $response, array $output = []): void
    {
        $run->update([
            'status' => AiRunStatus::Succeeded,
            'response' => $response,
            'output' => $output,
            'finished_at' => now(),
        ]);

        Log::channel('queue')->info('Magazine AI run completed.', $this->aiRunLogContext($run->refresh()));
    }

    private function failRun(AiRun $run, string $response, array $output = []): void
    {
        $run->update([
            'status' => AiRunStatus::Failed,
            'response' => $response,
            'output' => $output,
            'finished_at' => now(),
        ]);

        Log::channel('queue')->warning('Magazine AI run failed.', $this->aiRunLogContext($run->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function aiRunLogContext(AiRun $run): array
    {
        return [
            'ai_run_id' => $run->id,
            'ai_run_type' => $run->type->value,
            'ai_run_status' => $run->status->value,
            'content_topic_id' => $run->content_topic_id,
            'post_id' => $run->post_id,
            'provider' => $run->provider,
            'model' => $run->model,
            'duration_seconds' => $run->finished_at !== null && $run->started_at !== null
                ? $run->started_at->diffInSeconds($run->finished_at)
                : null,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    private function fallbackMarkdown(string $title, string $locale): string
    {
        if ($locale === 'de') {
            $germanTitle = $this->germanTitle($title);

            return "# {$germanTitle}\n\nDieser Beitrag erklärt das Thema praxisnah und mit Fokus auf finanzielle Souveränität.\n\n## Kerngedanke\n\nBitcoin kann als Werkzeug zur langfristigen Unabhängigkeit verstanden werden, wenn Risiko, Verwahrung und Zeithorizont sauber eingeordnet werden.\n\n## Praktische Schritte\n\n- Grundlagen lernen.\n- Sicherheitsmodell verstehen.\n- Kleine Beträge testen.\n- Entscheidungen regelmäßig aktualisieren.";
        }

        return "# {$title}\n\nThis article explains the topic with a practical focus on financial sovereignty.\n\n## Core idea\n\nBitcoin can be understood as a tool for long-term independence when risk, custody, and time horizon are handled carefully.\n\n## Practical steps\n\n- Learn the basics.\n- Understand the security model.\n- Test with small amounts.\n- Update decisions regularly.";
    }

    /**
     * @param  array<int, array<string, mixed>>  $englishBlocks
     * @return array<string, mixed>
     */
    private function fallbackTranslatedArticle(string $title, array $englishBlocks): array
    {
        $germanTitle = $this->germanTitle($title);

        return [
            'title' => $germanTitle,
            'excerpt' => 'Ein praxisnaher Magazinbeitrag über Bitcoin, Selbstverwahrung und finanzielle Souveränität.',
            'blocks' => $this->fallbackBlocks($germanTitle, 'de', $this->fallbackMarkdown($title, 'de')),
            'source_blocks' => count($englishBlocks),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackBlocks(string $title, string $locale, string $markdown): array
    {
        $copy = $locale === 'de'
            ? [
                'insight' => ['Kernaussage', 'Souveränität entsteht nicht durch Tempo, sondern durch klare Regeln, kleine Tests und wiederholbare Entscheidungen.'],
                'flow' => ['Entscheidungspfad', ['Ziel klären', 'Risiko bewerten', 'Verwahrung testen', 'Regelmäßig prüfen']],
                'checklist' => ['Prüfliste', ['Zeithorizont notieren', 'Seed-Aufbewahrung planen', 'Kleine Testtransaktion senden', 'Dokumentation aktualisieren']],
            ]
            : [
                'insight' => ['Core insight', 'Sovereignty improves when decisions are explicit, tested at small scale, and reviewed on a schedule.'],
                'flow' => ['Decision path', ['Clarify goal', 'Map risk', 'Test custody', 'Review regularly']],
                'checklist' => ['Field checklist', ['Write the time horizon', 'Plan seed storage', 'Send a small test transaction', 'Update the policy note']],
            ];

        return [
            [
                'type' => 'section',
                'heading' => $title,
                'anchor' => Str::slug($title, '-', $locale),
                'markdown' => $this->markdownWithoutLeadingHeading($markdown),
                'data' => [],
            ],
            [
                'type' => 'insight',
                'markdown' => null,
                'data' => [
                    'title' => $copy['insight'][0],
                    'body' => $copy['insight'][1],
                ],
            ],
            [
                'type' => 'flow_diagram',
                'markdown' => null,
                'data' => [
                    'title' => $copy['flow'][0],
                    'steps' => $copy['flow'][1],
                ],
            ],
            [
                'type' => 'checklist',
                'markdown' => null,
                'data' => [
                    'title' => $copy['checklist'][0],
                    'items' => $copy['checklist'][1],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackVisualBlock(string $locale): array
    {
        return [
            'type' => 'flow_diagram',
            'markdown' => null,
            'data' => $locale === 'de'
                ? ['title' => 'Entscheidungspfad', 'steps' => ['Ziel', 'Risiko', 'Test', 'Review']]
                : ['title' => 'Decision path', 'steps' => ['Goal', 'Risk', 'Test', 'Review']],
        ];
    }

    private function germanTitle(string $title): string
    {
        return match (Str::of($title)->lower()->toString()) {
            'why bitcoin custody matters' => 'Warum Bitcoin-Verwahrung wichtig ist',
            'bitcoin self custody threat models for beginners' => 'Bitcoin-Selbstverwahrung: Bedrohungsmodelle für Einsteiger',
            'why fiat debasement changes savings behavior' => 'Warum Fiat-Entwertung das Sparverhalten verändert',
            'how to build a personal bitcoin treasury policy' => 'Wie du eine persönliche Bitcoin-Treasury-Policy entwickelst',
            'financial independence without yield chasing' => 'Finanzielle Unabhängigkeit ohne Renditejagd',
            default => 'Souveräne Finanzstrategie',
        };
    }

    private function cleanMarkdown(mixed $markdown): string
    {
        if (! is_scalar($markdown)) {
            return '';
        }

        return Str::of((string) $markdown)
            ->replaceMatches('/<[^>]+>/', '')
            ->trim()
            ->limit(6000, '')
            ->toString();
    }

    private function markdownWithoutLeadingHeading(string $markdown): string
    {
        return Str::of($markdown)
            ->replaceMatches('/\A#{1,3}\s+.+\R{2,}/u', '')
            ->trim()
            ->toString();
    }

    private function cleanText(string $value, string $fallback): string
    {
        $cleaned = Str::of($value)
            ->replaceMatches('/<[^>]+>/', '')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit(240, '')
            ->toString();

        return filled($cleaned) ? $cleaned : $fallback;
    }

    private function excerpt(string $markdown, int $limit): string
    {
        return Str::of($markdown)
            ->replaceMatches('/[#*_`>\[\]\(\)]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit($limit)
            ->toString();
    }
}

<?php

namespace App\Services;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\ContentType;
use App\Enums\PostStatus;
use App\Models\AiRun;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Pillar;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\Locales;
use App\Support\ResponsiveImage;
use Illuminate\Database\Eloquent\Builder;
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
    private const EXCLUDED_CRYPTO_TERMS = [
        'ethereum',
        'ether',
        'solana',
        'cardano',
        'ripple',
        'dogecoin',
        'litecoin',
        'monero',
        'polkadot',
        'avalanche',
        'chainlink',
        'uniswap',
        'tron',
        'stellar',
        'cosmos',
        'tezos',
        'algorand',
        'tether',
        'stablecoin',
        'stablecoins',
        'memecoin',
        'meme coin',
        'meme-coin',
        'decentralized finance',
        'defi',
        'non-fungible token',
        'non-fungible tokens',
        'nft',
        'nfts',
        'token sale',
        'token sales',
        'token launch',
        'token launches',
        'initial coin offering',
        'initial coin offerings',
        'ico',
        'icos',
        'airdrop',
        'airdrops',
        'yield farming',
        'liquidity mining',
        'proof of stake',
        'proof-of-stake',
        'staking',
        'pump and dump',
        'pump-and-dump',
        'crypto speculation',
        'crypto-speculation',
        'cryptocurrency speculation',
        'cryptocurrency-speculation',
        'kryptospekulation',
        'krypto spekulation',
        'krypto-spekulation',
        'crypto investment',
        'crypto-investment',
        'cryptocurrency investment',
        'cryptocurrency-investment',
        'kryptoinvestment',
        'krypto investment',
        'krypto-investment',
        'eth',
        'xrp',
        'bnb',
        'usdt',
        'usdc',
        'doge',
        'ltc',
        'xmr',
        'avax',
    ];

    public function generatePost(ContentTopic $topic): Post
    {
        if (! $this->hasAiProviderKey() && ! config('magazine_ai.allow_fallback_publication', false)) {
            throw new RuntimeException('A configured AI provider is required to publish a German source article and its English translation.');
        }

        $this->ensureTopicIsAllowed($topic);
        $sourceLocale = $this->sourceLocale($topic);
        $this->alignTopicWithPrimaryEditorialLocale($topic, $sourceLocale);
        $briefingSources = $this->isBriefingTopic($topic) ? $this->briefingSources($topic) : [];

        if ($this->isBriefingTopic($topic) && count($briefingSources) < 2) {
            $topic->update([
                'status' => ContentTopicStatus::Archived,
            ]);

            Log::channel('queue')->warning('Briefing topic archived because its saved sources no longer pass verification.', [
                'content_topic_id' => $topic->id,
                'content_topic_title' => $topic->title,
            ]);

            throw new RuntimeException('Briefing topics require at least two credible independent sources before article generation.');
        }

        $draftRun = $this->startRun(AiRunType::Draft, $topic);
        $title = $topic->title;
        $internalLinks = $this->internalLinkCandidates($sourceLocale, topic: $topic);
        $draft = $this->promptWithFallback(
            instructions: $this->draftInstructions($topic),
            prompt: $this->draftPrompt($topic, $internalLinks, $briefingSources),
            fallback: $this->fallbackMarkdown($title, $sourceLocale),
        );
        $this->ensureGeneratedContentIsAllowed($topic, $draft, 'draft', run: $draftRun);
        $this->ensureGeneratedBriefingSourcesArePersisted(
            $topic,
            $draft,
            'draft',
            $briefingSources,
            run: $draftRun,
        );
        $this->finishRun($draftRun, $draft);

        $sourceSeo = $this->seoPlan($topic, $sourceLocale, $title, $draft, $internalLinks);
        $sourceTitle = $sourceSeo['article_title'];
        $sourceSlug = $this->uniqueTranslationSlug($sourceSeo['slug'], $sourceLocale);
        $sourceExcerpt = $sourceSeo['meta_description'];
        $this->ensureGeneratedContentIsAllowed($topic, $sourceSeo, 'SEO metadata');
        $sourceBlocks = $this->articleBlocks($topic, $sourceLocale, $sourceTitle, $draft, $sourceSeo['keywords'], $internalLinks);
        $sourceMarkdown = $this->markdownFromBlocks($sourceBlocks, $draft);
        $this->ensureGeneratedContentIsAllowed($topic, [$sourceMarkdown, $sourceBlocks], 'source article');
        $this->ensureGeneratedBriefingSourcesArePersisted(
            $topic,
            [$sourceSeo, $sourceMarkdown, $sourceBlocks],
            'source article',
            $briefingSources,
        );

        $post = Post::create([
            'content_topic_id' => $topic->id,
            'category_id' => $topic->category_id ?? $this->defaultCategory()->id,
            'content_type' => $topic->content_type ?? ContentType::Guide,
            'status' => PostStatus::Draft,
            'audience_level' => $topic->audience_level,
            'primary_language' => $sourceLocale,
            'scheduled_for' => $topic->scheduled_for,
            'sources' => $briefingSources,
            'seo' => [
                'keywords' => $sourceSeo['keywords'],
                'internal_links' => $internalLinks,
            ],
            'ai_metadata' => [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'auto_generated' => true,
                'generated_at' => now()->toAtomString(),
                'method' => 'automated_research_and_publishing_workflow',
            ],
        ]);

        $post->translations()->create([
            'locale' => $sourceLocale,
            'title' => $sourceTitle,
            'slug' => $sourceSlug,
            'excerpt' => $sourceExcerpt,
            'markdown' => $sourceMarkdown,
            'meta_title' => $sourceSeo['meta_title'],
            'meta_description' => $sourceSeo['meta_description'],
            'seo' => [
                'canonical_locale' => $sourceLocale,
                'keywords' => $sourceSeo['keywords'],
                'internal_links' => $internalLinks,
            ],
        ]);

        $translatedBlocks = [];

        foreach ($this->translationLocales($sourceLocale) as $locale) {
            $translationRun = $this->startRun(AiRunType::Translation, $topic, $post);
            $targetLanguage = Locales::language($locale)->englishName();
            $translatedArticle = $this->promptJson(
                instructions: "Translate Sovereign Manual articles into precise, natural {$targetLanguage}. Preserve the practical, calm editorial voice. Return only valid JSON. Keep article and section headings compact. Never leave source-language headings or UI-like labels untranslated unless they are proper nouns.",
                prompt: json_encode([
                    'source_locale' => $sourceLocale,
                    'target_locale' => $locale,
                    'target_language' => $targetLanguage,
                    'editorial_context' => $this->editorialContext(
                        $topic->category,
                        $topic->content_type ?? ContentType::Guide,
                        $locale,
                    ),
                    'title' => $sourceTitle,
                    'excerpt' => $sourceExcerpt,
                    'blocks' => $sourceBlocks,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );

            if ($translatedArticle === null && $this->hasAiProviderKey()) {
                $this->failRun($translationRun, 'AI translation did not return valid article JSON.', [
                    'locale' => $locale,
                ]);

                throw new RuntimeException('AI translation did not return valid article JSON.');
            }

            $translatedArticle ??= $this->fallbackTranslatedArticle($sourceTitle, $locale);
            $translatedTitle = $this->cleanText((string) ($translatedArticle['title'] ?? $title), $title);
            $blocks = $this->sanitizeBlocks($translatedArticle['blocks'] ?? null, $locale);

            if ($sourceBlocks !== [] && $this->hasAiProviderKey() && $blocks === []) {
                $this->failRun($translationRun, 'AI translation did not return usable article blocks.', [
                    'locale' => $locale,
                    'source_block_count' => count($sourceBlocks),
                ]);

                throw new RuntimeException('AI translation did not return usable article blocks.');
            }

            $localizedInternalLinks = $this->internalLinkCandidates($locale, $post->id, $topic);
            $localizedDraft = $this->markdownFromBlocks($blocks, $this->fallbackMarkdown($title, $locale));
            $localizedSeo = $this->seoPlan($topic, $locale, $translatedTitle, $localizedDraft, $localizedInternalLinks);
            $localizedSlug = $this->uniqueTranslationSlug($localizedSeo['slug'], $locale);
            $this->ensureGeneratedContentIsAllowed(
                $topic,
                [$translatedTitle, $localizedDraft, $localizedSeo, $blocks],
                "translation for {$locale}",
                $post,
                $translationRun,
            );
            $this->ensureGeneratedBriefingSourcesArePersisted(
                $topic,
                [$translatedTitle, $localizedDraft, $localizedSeo, $blocks],
                "translation for {$locale}",
                $briefingSources,
                $post,
                $translationRun,
            );
            $this->finishRun($translationRun, $localizedDraft, ['title' => $translatedTitle, 'locale' => $locale]);

            $post->translations()->create([
                'locale' => $locale,
                'title' => $translatedTitle,
                'slug' => $localizedSlug,
                'excerpt' => $localizedSeo['meta_description'],
                'markdown' => $localizedDraft,
                'meta_title' => $localizedSeo['meta_title'],
                'meta_description' => $localizedSeo['meta_description'],
                'seo' => [
                    'canonical_locale' => $locale,
                    'keywords' => $localizedSeo['keywords'],
                    'internal_links' => $localizedInternalLinks,
                ],
            ]);

            $translatedBlocks[$locale] = $blocks;
        }

        $this->createBlocks($post, $sourceLocale, $sourceBlocks);

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
    public function createTopicIdeas(int $count = 2, ContentType $contentType = ContentType::Guide): Collection
    {
        $category = $this->randomEvergreenCategory();
        $sourceLocale = $this->primaryEditorialLocale();
        $avoidTopics = $this->recentTopicTitles($category);
        $run = $this->startRun(AiRunType::Topic);
        $response = $this->promptWithFallback(
            instructions: 'You propose focused, evergreen topics for a practical self-determination publication. Return one topic per line in German only. Make each idea specific, helpful, bounded by the editorial safeguards, and clearly different from avoided existing topics.',
            prompt: json_encode([
                'task' => "Create {$count} evergreen article ideas for this category.",
                'output_language' => 'German',
                'editorial_context' => $this->editorialContext($category, $contentType, $sourceLocale),
                'audience' => 'German-speaking people who want to understand dependencies and build practical room to act in everyday life.',
                'avoid' => [...$this->editorialExclusions(), ...$avoidTopics],
                'style' => [
                    'concrete examples',
                    'category-specific angle',
                    'calm editorial phrasing',
                    'clear practical reader outcome',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            fallback: implode("\n", $this->fallbackTopicTitles($category, $contentType)),
        );

        $topics = Str::of($response)
            ->explode("\n")
            ->map(fn (string $line): string => trim(preg_replace('/^[\-\*\d\.\)\s]+/', '', $line) ?? ''))
            ->filter()
            ->filter(fn (string $title): bool => $this->isAllowedTopicText($title))
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
                    'primary_language' => $sourceLocale,
                    'target_languages' => $this->translationLocales($sourceLocale),
                    'scheduled_for' => now(),
                    'brief' => "AI-proposed evergreen {$contentType->value} topic with a practical, bounded, non-hype editorial framing.",
                    'content_type' => $contentType,
                    'constraints' => [
                        'tone' => 'clear, practical, non-hype',
                        'brand' => 'calm-editorial',
                        'category_key' => $category->key,
                        'content_type' => $contentType->value,
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
            $message = 'Bitcoin briefing ideation failed because the configured AI provider has no key for live web research.';
            Log::channel('queue')->warning($message, [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
            ]);
            $this->failRun($run, $message, ['created_topics' => [], 'reason' => 'missing_ai_provider_key']);

            throw new RuntimeException($message);
        }

        $briefingCategories = $this->briefingCategories();
        $briefingCategoryKeys = $briefingCategories->keys()->all();
        $avoidTopics = $briefingCategories
            ->flatMap(fn (Category $category): array => $this->recentTopicTitles($category))
            ->unique()
            ->take(24)
            ->values()
            ->all();
        $research = $this->promptStructuredJson(
            instructions: 'Research current Bitcoin developments for a selective Sovereign Manual briefing using live Google Search grounding. Return titles, summaries, credibility notes, and open questions in German only. Exclude altcoins, trading, price predictions, individual financial advice, and hype. Prefer primary, official, and technical sources; reputable reporting may provide supporting context. Treat social posts, anonymous blogs, exchanges, and aggregators as supporting context only. Use exact HTTPS source URLs from search results or citations. Do not invent URLs, dates, publishers, or article titles. Omit any source whose exact canonical URL is uncertain.',
            prompt: json_encode([
                'task' => 'Find a small set of current Bitcoin briefing candidates suitable for Sovereign Manual.',
                'output_language' => 'German',
                'current_date' => now()->toDateString(),
                'candidate_count' => max(6, $count * 6),
                'briefing_categories' => $briefingCategories
                    ->map(fn (Category $category): array => [
                        'key' => $category->key,
                        'name' => $category->name,
                        'description' => $category->description,
                    ])
                    ->values()
                    ->all(),
                'credibility_standard' => 'Each topic must have at least two independent credible sources explicitly supported by the search grounding citations. Credible source types are primary, official, technical, and reputable_reporting. Primary sources are preferred but not required. Include publication dates, direct source urls, source types, credibility notes, and unresolved uncertainties.',
                'important' => 'Return candidates discovered through search even when some sources are weak; classify weak sources as supporting. Do not return an empty topics array unless no suitable Bitcoin candidate can be found at all. The application will reject candidates that do not meet the final credibility threshold.',
                'avoid_similar_topics' => $avoidTopics,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            tools: [
                new WebSearch(maxSearches: 8),
            ],
            timeout: (int) config('magazine_ai.news_research_timeout', 180),
            briefingCategoryKeys: $briefingCategoryKeys,
        );

        if ($research === null) {
            $this->failRun($run, 'Bitcoin briefing ideation failed because the AI provider did not return valid structured research.', [
                'category_keys' => $briefingCategoryKeys,
            ]);

            throw new RuntimeException('Bitcoin briefing ideation failed because the AI provider did not return valid structured research.');
        }

        $topics = $this->createNewsTopicsFromResearch($research, $briefingCategories, $count, $avoidTopics);

        if ($topics->isEmpty()) {
            $this->failRun($run, 'Bitcoin briefing ideation found no topics with at least two credible independent sources.', [
                'category_keys' => $briefingCategoryKeys,
                ...$this->newsResearchDiagnostics($research),
            ]);

            throw new RuntimeException('Bitcoin briefing ideation found no topics with at least two credible independent sources.');
        }

        $this->finishRun($run, json_encode($research ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), [
            'created_topics' => $topics->pluck('id')->all(),
            'category_keys' => $briefingCategoryKeys,
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
                || ($asset->metadata['style'] ?? null) !== 'editorial-documentary')
            ->each(fn ($asset): bool => $asset->update(['status' => 'replaced']));

        $this->generatePostImage(
            $post,
            $post->contentTopic ?? new ContentTopic([
                'title' => $post->translation($post->primary_language)?->title ?? 'Practical guide',
                'audience_level' => $post->audience_level,
                'primary_language' => $post->primary_language,
                'content_type' => $post->content_type ?? ContentType::Guide,
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
    private function promptStructuredJson(
        string $instructions,
        string $prompt,
        array $tools = [],
        ?int $timeout = null,
        array $briefingCategoryKeys = [],
    ): ?array {
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
                            'category_key' => $schema->string()->enum($briefingCategoryKeys)->required(),
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
            )->prompt(
                $prompt,
                provider: config('magazine_ai.provider', 'gemini'),
                model: config('magazine_ai.text_model', 'gemini-2.5-flash'),
                timeout: $timeout,
            );

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

    /**
     * @return array<int, string>
     */
    private function translationLocales(string $sourceLocale): array
    {
        return collect(Locales::supported())
            ->reject(fn (string $locale): bool => $locale === $sourceLocale)
            ->values()
            ->all();
    }

    private function primaryEditorialLocale(): string
    {
        $locale = config('magazine_ai.primary_locale', 'de');

        return is_string($locale) && Locales::isSupported($locale) ? $locale : 'de';
    }

    private function sourceLocale(ContentTopic $topic): string
    {
        return $this->primaryEditorialLocale();
    }

    private function alignTopicWithPrimaryEditorialLocale(ContentTopic $topic, string $sourceLocale): void
    {
        if ($topic->primary_language === $sourceLocale
            && $topic->target_languages === $this->translationLocales($sourceLocale)) {
            return;
        }

        $topic->update([
            'primary_language' => $sourceLocale,
            'target_languages' => $this->translationLocales($sourceLocale),
        ]);
    }

    private function contentTypeValue(ContentTopic $topic): string
    {
        return ($topic->content_type ?? ContentType::Guide)->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function editorialContext(?Category $category, ContentType $contentType, string $locale): array
    {
        $localizedCategory = $category?->localized($locale);
        $localizedPillar = $localizedCategory?->pillar?->localized($locale);

        return [
            'brand' => 'Sovereign Manual: practical guides to greater independence in digital, financial, and everyday life.',
            'audience' => 'German-speaking people who want to understand dependencies and build practical room to act in everyday life.',
            'pillar' => [
                'key' => $localizedPillar?->key,
                'name' => $localizedPillar?->name,
                'description' => $localizedPillar?->description,
            ],
            'category' => [
                'key' => $localizedCategory?->key,
                'name' => $localizedCategory?->name,
                'description' => $localizedCategory?->description,
            ],
            'content_type' => $contentType->value,
            'editorial_boundaries' => $this->editorialExclusions(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function editorialExclusions(): array
    {
        return [
            'altcoins',
            'trading',
            'price predictions',
            'individual financial advice',
            'health or medical advice',
            'legal or tax advice',
            'fear-driven preparedness',
            'partisan politics',
        ];
    }

    private function ensureTopicIsAllowed(ContentTopic $topic): void
    {
        if ($this->isAllowedTopicText("{$topic->title} {$topic->brief}")) {
            return;
        }

        $this->archiveTopicForSafeguards($topic, 'topic', 'The topic falls outside Sovereign Manual editorial safeguards.');
    }

    private function ensureGeneratedContentIsAllowed(
        ContentTopic $topic,
        mixed $content,
        string $stage,
        ?Post $post = null,
        ?AiRun $run = null,
    ): void {
        $text = is_string($content)
            ? $content
            : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (is_string($text) && $this->isAllowedTopicText($text)) {
            return;
        }

        $message = 'Generated content falls outside Sovereign Manual editorial safeguards.';

        if ($run !== null) {
            $this->failRun($run, $message, ['stage' => $stage]);
        }

        $post?->delete();

        $this->archiveTopicForSafeguards(
            $topic,
            $stage,
            $message,
        );
    }

    /**
     * @param  array<int, array<string, string>>  $sources
     */
    private function ensureGeneratedBriefingSourcesArePersisted(
        ContentTopic $topic,
        mixed $content,
        string $stage,
        array $sources,
        ?Post $post = null,
        ?AiRun $run = null,
    ): void {
        if (! $this->isBriefingTopic($topic)) {
            return;
        }

        $allowedUrls = collect($sources)
            ->pluck('url')
            ->filter(fn (mixed $url): bool => is_string($url) && filled($url))
            ->map(fn (string $url): string => $this->normalizedUrl($url))
            ->all();
        $unexpectedUrls = collect($this->contentUrls($content))
            ->reject(fn (string $url): bool => $this->isFirstPartyUrl($url)
                || in_array($this->normalizedUrl($url), $allowedUrls, true))
            ->values()
            ->all();
        $unexpectedAttributions = collect($this->contentSourceAttributions($content))
            ->reject(fn (string $attribution): bool => $this->isVerifiedBriefingSourceAttribution($attribution, $sources))
            ->values()
            ->all();

        if ($unexpectedUrls === [] && $unexpectedAttributions === []) {
            return;
        }

        $message = 'Generated briefing content references a source outside the verified source set.';

        if ($run !== null) {
            $this->failRun($run, $message, ['stage' => $stage]);
        }

        $post?->delete();
        $topic->update([
            'status' => ContentTopicStatus::Archived,
        ]);

        Log::channel('queue')->warning('Magazine briefing archived because generated content cited an unverified source.', [
            'content_topic_id' => $topic->id,
            'stage' => $stage,
            'unexpected_source_hosts' => collect($unexpectedUrls)
                ->map(fn (string $url): ?string => $this->urlHost($url))
                ->filter()
                ->values()
                ->all(),
            'unexpected_source_attribution_count' => count($unexpectedAttributions),
        ]);

        throw new RuntimeException($message);
    }

    /**
     * @return array<int, string>
     */
    private function contentUrls(mixed $content): array
    {
        $text = is_string($content)
            ? $content
            : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($text)) {
            return [];
        }

        preg_match_all("~(?<![@\\w.-])(?:(?:https?://|www\\.)[^\\s<>\"'`()\\[\\]{}]+|(?:[a-z0-9-]+\\.)+[a-z]{2,}(?:/[^\\s<>\"'`()\\[\\]{}]*)?)~iu", $text, $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $url): string => rtrim($url, '.,;:!?'))
            ->map(fn (string $url): string => str_starts_with(Str::lower($url), 'http') ? $url : "https://{$url}")
            ->filter(fn (string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->unique(fn (string $url): string => $this->normalizedUrl($url))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function contentSourceAttributions(mixed $content): array
    {
        $text = is_string($content)
            ? $content
            : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($text)) {
            return [];
        }

        preg_match_all(
            "/(?<![\\p{L}\\p{N}])(?i:according\\s+to|as\\s+reported\\s+by|reported\\s+by|as\\s+announced\\s+by|laut|gemäß|nach\\s+angaben\\s+(?:von|des|der)|nach\\s+aussage\\s+(?:von|des|der))\\s+(?:(?i:the|a|an|dem|den|der|des)\\s+)?((?:\\p{Lu}[\\p{L}\\p{M}\\d&’'-]*)(?:\\s+(?:(?:\\p{Lu}[\\p{L}\\p{M}\\d&’'-]*)|(?i:of|the|and|for|von|für|der|die|des|den|im|in|zu|zur|zum))){0,8})/u",
            $text,
            $matches,
        );

        return collect($matches[1] ?? [])
            ->filter(fn (mixed $attribution): bool => is_string($attribution) && filled($attribution))
            ->map(fn (string $attribution): string => trim($attribution))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, string>>  $sources
     */
    private function isVerifiedBriefingSourceAttribution(string $attribution, array $sources): bool
    {
        $normalizedAttribution = $this->normalizedSourceAttribution($attribution);

        return $normalizedAttribution !== ''
            && collect($sources)
                ->flatMap(fn (array $source): array => [
                    $source['publisher'] ?? null,
                    $source['title'] ?? null,
                ])
                ->filter(fn (mixed $source): bool => is_string($source) && filled($source))
                ->map(fn (string $source): string => $this->normalizedSourceAttribution($source))
                ->filter()
                ->contains(fn (string $source): bool => Str::contains($normalizedAttribution, $source));
    }

    private function normalizedSourceAttribution(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function isFirstPartyUrl(string $url): bool
    {
        $appUrl = config('app.url');

        return is_string($appUrl)
            && $this->urlHost($appUrl) !== null
            && $this->urlHost($url) === $this->urlHost($appUrl);
    }

    private function archiveTopicForSafeguards(ContentTopic $topic, string $stage, string $message): never
    {
        $topic->update([
            'status' => ContentTopicStatus::Archived,
        ]);

        Log::channel('queue')->warning('Magazine content archived by editorial safeguards.', [
            'content_topic_id' => $topic->id,
            'stage' => $stage,
        ]);

        throw new RuntimeException($message);
    }

    private function isAllowedTopicText(string $text): bool
    {
        $normalizedText = Str::of($text)->lower()->toString();

        return ! Str::contains($normalizedText, [
            'altcoin',
            'trading',
            'traden',
            'price prediction',
            'price forecast',
            'price target',
            'kursprognose',
            'kursziel',
            'anlageberatung',
            'finanzberatung',
            'individuelle finanz',
            'anlageempfehl',
            'financial advice',
            'investment recommendation',
            'investment advice',
            'medical advice',
            'medical diagnosis',
            'health treatment',
            'health advice',
            'gesundheitsberatung',
            'gesundheitliche beratung',
            'medizinische diagnose',
            'medizinischer rat',
            'legal advice',
            'tax advice',
            'rechtsberatung',
            'rechtsrat',
            'steuerberatung',
            'steuerlicher rat',
            'prepper',
            'survivalism',
            'ideological self-sufficiency',
            'ideologische autarkie',
        ]) && ! $this->containsExcludedCryptoTopic($normalizedText);
    }

    private function containsExcludedCryptoTopic(string $text): bool
    {
        $terms = implode('|', array_map(
            static fn (string $term): string => preg_quote($term, '/'),
            self::EXCLUDED_CRYPTO_TERMS,
        ));

        return preg_match('/(?<![\p{L}\p{N}])(?:'.$terms.')(?![\p{L}\p{N}])/iu', $text) === 1;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function briefingSources(ContentTopic $topic): array
    {
        $research = $topic->constraints['news_research'] ?? [];

        return is_array($research)
            ? $this->credibleSources(
                $research['sources'] ?? [],
                $this->groundingCitations($research['grounding_citations'] ?? []),
            )->values()->all()
            : [];
    }

    private function draftInstructions(ContentTopic $topic): string
    {
        $language = Locales::language($this->sourceLocale($topic))->englishName();
        $contentType = $this->contentTypeValue($topic);
        $context = json_encode(
            $this->editorialContext($topic->category, $topic->content_type ?? ContentType::Guide, $this->sourceLocale($topic)),
            JSON_UNESCAPED_UNICODE,
        );

        if ($this->isBriefingTopic($topic)) {
            return "You write a sourced Sovereign Manual briefing in clear {$language} Markdown. Lead with verified facts, explain why the development matters, distinguish fact from uncertainty, cite the provided sources naturally, and do not add individual recommendations. Follow this editorial context exactly: {$context}";
        }

        return "You write a practical Sovereign Manual {$contentType} in clear {$language} Markdown. Use concise article and section headings. Build a clear SEO-friendly structure with short paragraphs, useful lists, concrete examples, category-specific reader outcomes, and naturally repeated keywords. Do not keyword-stuff or add a related-reading section. Do not give individual financial, health, legal, tax, or political advice. Follow this editorial context exactly: {$context}";
    }

    /**
     * @param  array<int, array{title: string, url: string, slug: string}>  $internalLinks
     */
    private function draftPrompt(ContentTopic $topic, array $internalLinks, array $briefingSources = []): string
    {
        return json_encode([
            'task' => "Write a practical article for {$topic->audience_level} readers.",
            'editorial_context' => $this->editorialContext(
                $topic->category,
                $topic->content_type ?? ContentType::Guide,
                $this->sourceLocale($topic),
            ),
            'topic' => $topic->title,
            'brief' => $topic->brief,
            'research' => $this->isBriefingTopic($topic) ? [
                'verified_sources' => $briefingSources,
                'source_policy' => 'Ground facts only in these verified sources. Do not cite, link to, or name any other source or publisher, and do not add a references section; verified sources are rendered separately.',
            ] : null,
            'internal_link_candidates' => $internalLinks,
            'quality_bar' => [
                'write like a careful editor, not a generic AI assistant',
                'include concrete examples and practical implications',
                'avoid repeated wording from recent articles',
                'never invent sources, legal claims, medical claims, or individual recommendations',
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
        if (! $this->hasAiProviderKey()) {
            return [];
        }

        $run = $this->startRun(AiRunType::Outline, $topic);
        $feedback = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->promptJson(
                instructions: 'Convert an educational Sovereign Manual article into a structured editorial block plan. Return only valid JSON. Preserve the full article detail. Do not summarize, shorten, or omit practical examples. Split the full draft into section blocks with several paragraphs each. Keep every section heading compact, ideally 3 to 7 words. Naturally use the provided SEO keywords in headings and body text where they fit. Add relevant internal Markdown links from the provided candidates. When the first candidate is the relevant pillar hub, link to it once where it gives the reader useful orientation; do not add a generic latest-articles section. Use these block types only: section, insight, checklist, flow_diagram, sketch. Use section blocks for all prose sections with an explicit heading column, anchor column, and markdown body that does not repeat the heading and does not contain nested H2 headings. Place visual/support blocks immediately after the section they clarify; do not group insight, checklist, flow_diagram, or sketch blocks at the end of the article. Visual blocks may supplement the article, but must not replace section text. Store every process, tree, branch, comparison, sequence, timeline, or relationship diagram as a flow_diagram block with a structured data.diagram object, never as a Markdown code fence. Use data.diagram.kind to describe the diagram family. Do not include raw HTML, raw SVG, or Mermaid syntax.',
                prompt: json_encode([
                    'locale' => $locale,
                    'topic' => $topic->title,
                    'audience_level' => $topic->audience_level,
                    'brief' => $topic->brief,
                    'seo_keywords' => $keywords,
                    'internal_link_candidates' => $internalLinks,
                    'markdown' => $markdown,
                    'previous_attempt_feedback' => $feedback,
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
                                'data' => [
                                    'title' => 'Flow title',
                                    'diagram' => [
                                        'kind' => 'flowchart',
                                        'direction' => 'LR',
                                        'rows' => [
                                            ['Shared start', 'Path one', 'Outcome one'],
                                            ['Shared start', 'Path two', 'Outcome two'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'sketch',
                                'markdown' => null,
                                'data' => ['title' => 'Sketch title', 'caption' => 'Short caption', 'labels' => ['Label one', 'Label two']],
                            ],
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );

            $blocks = $this->sanitizeBlocks($this->extractBlockPlanBlocks($response), $locale);
            $sectionCount = collect($blocks)->where('type', 'section')->count();
            $feedback = 'Return at least three usable section blocks in the requested block schema.';

            if ($sectionCount >= 3) {
                $this->finishRun($run, $this->markdownFromBlocks($blocks, ''), [
                    'attempts' => $attempt,
                    'block_count' => count($blocks),
                    'section_count' => $sectionCount,
                    'block_types' => collect($blocks)->pluck('type')->values()->all(),
                ]);

                return $blocks;
            }
        }

        $this->failRun($run, 'AI block planning did not return usable article blocks.', [
            'feedback' => $feedback,
        ]);

        throw new RuntimeException('AI block planning did not return usable article blocks.');
    }

    /**
     * @return array<int, mixed>|null
     */
    private function extractBlockPlanBlocks(?array $response): ?array
    {
        if ($response === null) {
            return null;
        }

        if (is_array($response['blocks'] ?? null)) {
            return $response['blocks'];
        }

        return array_is_list($response) ? $response : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeBlocks(mixed $blocks, string $locale): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->filter(fn (mixed $block): bool => is_array($block))
            ->map(function (array $block) use ($locale): ?array {
                $type = $block['type'] ?? null;

                if ($type === 'section') {
                    $heading = $this->seoText((string) ($block['heading'] ?? ''), 'Practice', 72);
                    $anchor = filled($heading) ? Str::slug((string) ($block['anchor'] ?? $heading), '-', $locale) : null;
                    $markdown = $this->cleanMarkdown($block['markdown'] ?? null);

                    if (! filled($heading) || ! filled($anchor) || ! filled($markdown)) {
                        return null;
                    }

                    return [
                        'type' => 'section',
                        'post_asset_id' => null,
                        'heading' => $heading,
                        'anchor' => $anchor,
                        'markdown' => $markdown,
                        'data' => [],
                    ];
                }

                if (! in_array($type, ['insight', 'checklist', 'flow_diagram', 'sketch', 'image'], true)) {
                    return null;
                }

                $data = is_array($block['data'] ?? null) ? $this->sanitizeBlockData($type, $block['data']) : [];

                return [
                    'type' => $type,
                    'post_asset_id' => $type === 'image' ? $this->sanitizePostAssetId($block['post_asset_id'] ?? null) : null,
                    'heading' => null,
                    'anchor' => null,
                    'markdown' => null,
                    'data' => $data,
                ];
            })
            ->filter(function (array $block): bool {
                if ($block['type'] === 'image') {
                    return ($block['post_asset_id'] ?? null) !== null;
                }

                return true;
            })
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeBlockData(string $type, array $data): array
    {
        if ($type === 'flow_diagram') {
            return $this->sanitizeFlowDiagramData($data);
        }

        return $this->sanitizeScalarBlockData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeScalarBlockData(array $data): array
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

    private function sanitizePostAssetId(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeFlowDiagramData(array $data): array
    {
        $sanitized = [];
        $title = is_scalar($data['title'] ?? null) ? $this->cleanText((string) $data['title'], '') : '';
        $diagram = is_array($data['diagram'] ?? null) ? $data['diagram'] : $data;
        $kind = is_scalar($diagram['kind'] ?? null) ? Str::of((string) $diagram['kind'])->lower()->replaceMatches('/[^a-z0-9_-]+/', '_')->trim('_')->toString() : 'flowchart';
        $direction = is_scalar($diagram['direction'] ?? null) ? strtoupper(trim((string) $diagram['direction'])) : 'LR';
        $rows = collect($diagram['rows'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(fn (array $row): array => collect($row)
                ->filter(fn (mixed $step): bool => is_scalar($step))
                ->map(fn (mixed $step): string => $this->cleanText((string) $step, ''))
                ->filter()
                ->take(6)
                ->values()
                ->all())
            ->filter(fn (array $row): bool => count($row) >= 2)
            ->take(6)
            ->values()
            ->all();
        $steps = collect($diagram['steps'] ?? [])
            ->filter(fn (mixed $step): bool => is_scalar($step))
            ->map(fn (mixed $step): string => $this->cleanText((string) $step, ''))
            ->filter()
            ->take(6)
            ->values()
            ->all();

        if (filled($title)) {
            $sanitized['title'] = $title;
        }

        $sanitized['diagram'] = [
            'kind' => filled($kind) ? $kind : 'flowchart',
        ];

        if (in_array($direction, ['LR', 'RL', 'TB', 'TD', 'BT'], true)) {
            $sanitized['diagram']['direction'] = $direction;
        }

        if ($rows !== []) {
            $sanitized['diagram']['rows'] = $rows;
        } elseif (count($steps) >= 2) {
            $sanitized['diagram']['steps'] = $steps;
        }

        return $sanitized;
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
                'post_asset_id' => $block['post_asset_id'] ?? null,
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
        $sourceLocale = $post->primary_language;
        $prompt = $this->editorialImagePrompt($topic);
        $altTexts = $this->imageAltTexts($post, $topic);
        $run = $this->startRun(AiRunType::Image, $topic, $post, config('magazine_ai.image_model', 'gemini-2.5-flash-image'));
        $imageSlug = $post->translation($sourceLocale)?->slug ?? Str::slug($topic->title);

        if (! filled($imageSlug)) {
            $imageSlug = "post-{$post->id}";
        }

        $metadata = [
            'style' => 'editorial-documentary',
            'role' => 'header',
            'prompt_version' => 3,
            'no_unsplash' => true,
            'alt_texts' => $altTexts,
        ];

        if (! config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key')) {
            $post->assets()->create([
                'type' => 'image',
                'locale' => $sourceLocale,
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => $altTexts[$sourceLocale] ?? $this->fallbackImageAltText($post, $topic, $sourceLocale),
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

            $temporaryPath = $image->storePubliclyAs("post-assets/{$post->id}", "{$imageSlug}.png", 'public');
            $path = is_string($temporaryPath)
                ? app(ResponsiveImage::class)->convertToJpeg('public', $temporaryPath, "post-assets/{$post->id}/{$imageSlug}.jpg") ?? $temporaryPath
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
                'locale' => $sourceLocale,
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => $altTexts[$sourceLocale] ?? $this->fallbackImageAltText($post, $topic, $sourceLocale),
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
                'locale' => $sourceLocale,
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => $altTexts[$sourceLocale] ?? $this->fallbackImageAltText($post, $topic, $sourceLocale),
                'status' => 'pending',
                'metadata' => $metadata + ['error' => $exception->getMessage()],
            ]);

            $this->finishRun($run, $exception->getMessage(), $metadata + ['failed' => true]);
        }
    }

    private function editorialImagePrompt(ContentTopic $topic): string
    {
        $context = json_encode(
            $this->editorialContext(
                $topic->category,
                $topic->content_type ?? ContentType::Guide,
                $this->sourceLocale($topic),
            ),
            JSON_UNESCAPED_UNICODE,
        );

        return "Full-bleed documentary editorial header image for article topic: {$topic->title}. Audience level: {$topic->audience_level}. Context: {$context}. Calm, human-scale visual language: natural light, hands, paper, thoughtfully used everyday tools, rooms, details, or non-identifiable silhouettes where appropriate. Warm, tactile, restrained, modern editorial character with natural imperfections and ample readable negative space. Use the Bitcoin symbol or orange only when the topic is explicitly about Bitcoin. No neon, cyberpunk, abstract crypto ledgers, futuristic grids, propaganda, survivalism, stock-photo look, glossy AI aesthetic, text, logos, identifiable people, device mockups, book covers, posters, or page layouts.";
    }

    /**
     * @return array<string, string>
     */
    private function imageAltTexts(Post $post, ContentTopic $topic): array
    {
        $fallback = collect(Locales::supported())
            ->mapWithKeys(fn (string $locale): array => [
                $locale => $this->fallbackImageAltText($post, $topic, $locale),
            ])
            ->all();

        if (! $this->hasAiProviderKey()) {
            return $fallback;
        }

        $response = $this->promptJson(
            instructions: 'Write concise accessible alt text for an editorial article header image. Return only valid JSON. Describe the image meaningfully for screen readers. Do not include prompt/style labels such as AI art, background, or illustration unless visually necessary. Translate each alt text naturally for its locale.',
            prompt: json_encode([
                'topic' => $topic->title,
                'audience_level' => $topic->audience_level,
                'article_titles' => $post->translations()
                    ->get(['locale', 'title'])
                    ->pluck('title', 'locale')
                    ->all(),
                'locales' => Locales::supported(),
                'limits' => [
                    'max_characters' => 140,
                ],
                'schema' => [
                    'alt_texts' => collect(Locales::supported())
                        ->mapWithKeys(fn (string $locale): array => [
                            $locale => 'Concise alt text',
                        ])
                        ->all(),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        $generated = is_array($response['alt_texts'] ?? null) ? $response['alt_texts'] : [];

        return collect(Locales::supported())
            ->mapWithKeys(fn (string $locale): array => [
                $locale => Str::of($this->cleanText(
                    is_scalar($generated[$locale] ?? null) ? (string) $generated[$locale] : '',
                    $fallback[$locale],
                ))->limit(140, '')->toString(),
            ])
            ->all();
    }

    private function fallbackImageAltText(Post $post, ContentTopic $topic, string $locale): string
    {
        $title = $post->translation($locale)?->title
            ?? $post->translation(Locales::fallback())?->title
            ?? $topic->title;

        return "Header image for the article {$title}.";
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
                instructions: 'Create SEO metadata for a practical Sovereign Manual article. Return only valid JSON. Generate the visible H1 article_title, browser/search meta_title, and meta_description directly at the correct length. They may differ slightly. Do not return overlong text for PHP to shorten later. Keep article_title readable and specific, up to 70 characters. Keep meta_title compelling and specific, up to 60 characters. Keep meta_description as a complete description up to 160 characters. Never end meta_description with an ellipsis. Avoid hype and individual advice. Identify relevant keywords that should appear naturally in headings, body copy, and meta tags.',
                prompt: json_encode([
                    'locale' => $locale,
                    'topic' => $topic->title,
                    'brief' => $topic->brief,
                    'editorial_context' => $this->editorialContext(
                        $topic->category,
                        $topic->content_type ?? ContentType::Guide,
                        $locale,
                    ),
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

            if (str_ends_with($plan['meta_description'], '...')) {
                $problems[] = 'meta_description must be complete and must not end with an ellipsis';
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
        $articleTitle = $this->seoText($title, 'Practical guide', 70);
        $keywords = $this->keywordsFor($topic, $locale);

        return [
            'article_title' => $articleTitle,
            'meta_title' => $this->seoText($articleTitle, $articleTitle, 60),
            'meta_description' => $this->seoText($this->excerpt($markdown, 160), 'A practical guide to greater independence in everyday life.', 160),
            'slug' => $this->shortSlug($articleTitle, $locale),
            'keywords' => $keywords,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keywordsFor(ContentTopic $topic, string $locale): array
    {
        $context = $this->editorialContext(
            $topic->category,
            $topic->content_type ?? ContentType::Guide,
            $locale,
        );

        return collect([
            $context['pillar']['name'] ?? null,
            $context['category']['name'] ?? null,
            $this->contentTypeValue($topic),
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
    private function internalLinkCandidates(string $locale, ?int $excludePostId = null, ?ContentTopic $topic = null): array
    {
        $pillarKey = $topic?->category?->pillar?->key;
        $pillarHub = $this->pillarHubCandidate($topic, $locale);

        $posts = Post::query()
            ->with(['category', 'translations'])
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->when($excludePostId !== null, fn ($query) => $query->whereKeyNot($excludePostId))
            ->when(
                filled($pillarKey),
                fn (Builder $query) => $query->whereHas(
                    'category.pillar',
                    fn (Builder $pillarQuery) => $pillarQuery->where('key', $pillarKey),
                ),
            )
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

        return $pillarHub === null ? $posts : [$pillarHub, ...$posts];
    }

    /**
     * @return array{title: string, url: string, slug: string}|null
     */
    private function pillarHubCandidate(?ContentTopic $topic, string $locale): ?array
    {
        $pillar = $topic?->category?->pillar;

        if ($pillar === null) {
            return null;
        }

        $localizedPillar = $pillar->localized($locale);
        $categoryKeys = Category::query()
            ->where('pillar_id', $localizedPillar->id)
            ->pluck('key');
        $hasEnoughArticles = Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->whereHas('category', fn (Builder $query) => $query->whereIn('key', $categoryKeys))
            ->count() >= 6;

        if (! $hasEnoughArticles) {
            return null;
        }

        return [
            'title' => $localizedPillar->name,
            'url' => $this->localizedRoute($locale, 'pillar.show', [
                'pillar' => $localizedPillar->slug,
            ]),
            'slug' => $localizedPillar->slug,
        ];
    }

    private function defaultCategory(): Category
    {
        $locale = $this->primaryEditorialLocale();
        $category = Category::query()->firstOrCreate(
            ['key' => 'self-custody', 'lang' => Locales::language($this->primaryEditorialLocale())],
            [
                'slug' => 'selbstverwahrung',
                'name' => 'Selbstverwahrung',
                'description' => 'Praktische Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
            ]
        );

        if ($category->pillar_id === null) {
            $category->updateQuietly([
                'pillar_id' => Pillar::query()
                    ->where('key', 'bitcoin-money')
                    ->where('lang', Locales::language($locale))
                    ->value('id'),
            ]);
        }

        return $category;
    }

    private function randomEvergreenCategory(): Category
    {
        return Category::query()
            ->where('lang', Locales::language($this->primaryEditorialLocale()))
            ->whereNotIn('key', ['news', 'tools-practice'])
            ->inRandomOrder()
            ->first()
            ?? $this->defaultCategory();
    }

    /**
     * @return Collection<string, Category>
     */
    private function briefingCategories(): Collection
    {
        $categories = Category::query()
            ->where('lang', Locales::language($this->primaryEditorialLocale()))
            ->whereIn('key', ['self-custody', 'financial-sovereignty', 'economics'])
            ->get()
            ->keyBy('key');

        if (! $categories->has('self-custody')) {
            $category = $this->defaultCategory();
            $categories->put($category->key, $category);
        }

        return $categories;
    }

    /**
     * @return array<int, string>
     */
    private function fallbackTopicTitles(Category $category, ContentType $contentType): array
    {
        $formatPrefix = match ($contentType) {
            ContentType::Checklist => 'Checkliste: ',
            ContentType::Analysis => 'Analyse: ',
            default => '',
        };

        return collect(match ($category->key) {
            'privacy-security' => [
                'Eine einfache Bedrohungsanalyse für deinen digitalen Alltag erstellen',
                'Sichere digitale Gewohnheiten ohne unnötige Komplexität',
            ],
            'financial-sovereignty' => [
                'Eigene finanzielle Regeln dokumentieren und Grenzen klar erkennen',
                'Wie langfristige Spargewohnheiten Entscheidungen vereinfachen',
            ],
            'family-legacy' => [
                'Wichtige Informationen für Angehörige verständlich dokumentieren',
                'Eine vorsichtige Checkliste für persönliche Notfallunterlagen',
            ],
            'tools-practice' => [
                'Digitale Werkzeuge vor dem Alltagseinsatz sicher testen',
                'Eine praktische Checkliste für wiederholbare Abläufe',
            ],
            'economics' => [
                'Was Geldgeschichte für langfristige Entscheidungen lehrt',
                'Wie Anreize und Knappheit unsere Entscheidungen prägen',
            ],
            'mindset' => [
                'Wie klare Entscheidungsregeln emotionale Fehler reduzieren',
                'Langfristig denken, ohne die Gegenwart auszublenden',
            ],
            default => [
                'Ein praktischer erster Schritt zu mehr Eigenständigkeit',
                'Wie du wichtige Abläufe verständlich dokumentierst',
            ],
        })
            ->map(fn (string $title): string => $formatPrefix.$title)
            ->all();
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
            ->flatMap(fn (Post $post): array => $post->translations->pluck('title')->all());

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

    private function isBriefingTopic(ContentTopic $topic): bool
    {
        return ($topic->content_type ?? ContentType::Guide) === ContentType::Briefing;
    }

    /**
     * @return Collection<int, ContentTopic>
     */
    private function createNewsTopicsFromResearch(?array $research, Collection $categories, int $count, array $avoidTopics): Collection
    {
        $groundingCitations = $this->groundingCitations($research['grounding_citations'] ?? []);

        $topics = collect($research['topics'] ?? [])
            ->filter(fn (mixed $topic): bool => is_array($topic))
            ->filter(fn (array $topic): bool => filled($topic['title'] ?? null))
            ->filter(fn (array $topic): bool => $categories->has($topic['category_key'] ?? null))
            ->map(function (array $topic) use ($groundingCitations): array {
                $topic['credible_sources'] = $this->credibleSources(
                    $topic['sources'] ?? [],
                    $groundingCitations,
                )->values()->all();

                return $topic;
            })
            ->filter(fn (array $topic): bool => count($topic['credible_sources']) >= 2)
            ->filter(fn (array $topic): bool => $this->isAllowedTopicText((string) $topic['title']))
            ->take($count)
            ->values()
            ->map(function (array $topic, int $index) use ($categories, $avoidTopics, $groundingCitations): ContentTopic {
                $title = $this->cleanText((string) $topic['title'], 'Bitcoin briefing');
                $category = $categories->get($topic['category_key']);
                $sources = $topic['credible_sources'];
                $credibilityNotes = $this->stringList($topic['credibility_notes'] ?? []);
                $openQuestions = $this->stringList($topic['open_questions'] ?? []);
                $brief = $this->cleanText((string) ($topic['summary'] ?? ''), 'Current Bitcoin development requiring sourced context.');

                return ContentTopic::firstOrCreate(
                    ['slug' => Str::slug($title)],
                    [
                        'title' => $title,
                        'category_id' => $category->id,
                        'status' => ContentTopicStatus::Scheduled,
                        'priority' => max(1, 10 - $index),
                        'audience_level' => 'intermediate',
                        'primary_language' => $this->primaryEditorialLocale(),
                        'target_languages' => $this->translationLocales($this->primaryEditorialLocale()),
                        'scheduled_for' => now(),
                        'brief' => $brief,
                        'content_type' => ContentType::Briefing,
                        'constraints' => [
                            'tone' => 'clear, sourced, non-hype',
                            'brand' => 'calm-editorial',
                            'category_key' => $category->key,
                            'content_type' => ContentType::Briefing->value,
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
            Log::channel('queue')->warning('Bitcoin briefing ideation produced no topics that met the credibility threshold.', [
                'required_credible_sources' => 2,
                'candidate_topics' => is_countable($research['topics'] ?? null) ? count($research['topics']) : 0,
            ]);
        }

        return $topics;
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function credibleSources(mixed $sources, array $groundingCitations = []): Collection
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
            ->filter(fn (array $source): bool => $this->sourceIsGrounded($source, $groundingCitations))
            ->filter(fn (array $source): bool => $this->sourceUrlIsReachable($source['url']))
            ->unique(fn (array $source): string => $this->urlHost($source['url']) ?? $this->normalizedUrl($source['url']))
            ->values();
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

    /**
     * @param  array<string, string>  $source
     * @param  array<int, array{title: string, url: string}>  $groundingCitations
     */
    private function sourceIsGrounded(array $source, array $groundingCitations): bool
    {
        $sourceUrl = $this->normalizedUrl($source['url']);
        $sourceHost = $this->urlHost($source['url']);

        return collect($groundingCitations)->contains(function (array $citation) use ($sourceUrl, $sourceHost): bool {
            if ($this->normalizedUrl($citation['url']) === $sourceUrl) {
                return true;
            }

            return $this->isGoogleGroundingRedirect($citation['url'])
                && $sourceHost !== null
                && $this->domainFromText($citation['title']) === $sourceHost;
        });
    }

    private function isGoogleGroundingRedirect(string $url): bool
    {
        return $this->urlHost($url) === 'vertexaisearch.cloud.google.com'
            && str_contains((string) parse_url($url, PHP_URL_PATH), '/grounding-api-redirect/');
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
            $request = Http::timeout(5)
                ->connectTimeout(3)
                ->withUserAgent('SovereignManualBot/1.0 (+https://sovereignmanual.com)');

            $response = $request
                ->head($url);

            if ($response->successful()) {
                return true;
            }

            if ($response->status() !== 405) {
                return false;
            }

            $response = $request->get($url);

            return $response->successful();
        } catch (Throwable $exception) {
            Log::channel('queue')->warning('Briefing source URL verification failed.', [
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

        while (PostTranslation::query()->where('locale', $locale)->where('slug', $slug)->exists()) {
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

        return filled($words) ? $words : 'practical-guide';
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
            return "# {$title}\n\nDieser Artikel ordnet das Thema praktisch ein und zeigt, wie du im Alltag eigenständiger entscheiden kannst.\n\n## Kernidee\n\nVerstehe zuerst die Abhängigkeit, die realistischen Optionen und die jeweiligen Abwägungen.\n\n## Praktische Schritte\n\n- Die Grundlagen nachvollziehen.\n- Wichtige Informationen dokumentieren.\n- Kleine, reversible Veränderungen testen.\n- Den Ansatz bei neuen Umständen überprüfen.";
        }

        return "# {$title}\n\nThis article explains the topic with a practical focus on more independent everyday decisions.\n\n## Core idea\n\nStart by understanding the dependency involved, the realistic choices available, and the trade-offs of each option.\n\n## Practical steps\n\n- Learn the basics.\n- Document what matters.\n- Test small, reversible changes.\n- Review the approach when circumstances change.";
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackTranslatedArticle(string $title, string $locale): array
    {
        return [
            'title' => $title,
            'excerpt' => $locale === 'de'
                ? 'Eine praktische Anleitung für mehr Eigenständigkeit im Alltag.'
                : 'A practical guide to greater independence in everyday life.',
            'blocks' => [],
        ];
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
            ->limit($limit, '', true)
            ->rtrim(' ,.;:-')
            ->toString();
    }
}

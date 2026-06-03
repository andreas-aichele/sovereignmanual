<?php

namespace App\Services;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Models\AiRun;
use App\Models\ContentTopic;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;
use Throwable;

use function Laravel\Ai\agent;

class MagazineAiPipeline
{
    public function generatePost(ContentTopic $topic): Post
    {
        $draftRun = $this->startRun(AiRunType::Draft, $topic);
        $title = $topic->title;
        $englishSlug = Str::slug($title);
        $draft = $this->promptWithFallback(
            instructions: 'You write educational, non-hype Bitcoin and financial independence articles in clear English Markdown.',
            prompt: "Write a practical article for {$topic->audience_level} readers.\nTopic: {$topic->title}\nBrief: {$topic->brief}",
            fallback: $this->fallbackMarkdown($title, 'en'),
        );
        $this->finishRun($draftRun, $draft);

        $englishExcerpt = $this->excerpt($draft, 180);
        $englishBlocks = $this->articleBlocks($topic, 'en', $title, $draft);
        $englishMarkdown = $this->markdownFromBlocks($englishBlocks, $draft);

        $post = Post::create([
            'content_topic_id' => $topic->id,
            'slug' => $englishSlug,
            'status' => PostStatus::Published,
            'topic' => $topic->title,
            'audience_level' => $topic->audience_level,
            'primary_language' => $topic->primary_language,
            'published_at' => now(),
            'scheduled_for' => $topic->scheduled_for,
            'seo' => [
                'keywords' => ['bitcoin', 'financial intelligence', 'self custody'],
            ],
            'ai_metadata' => [
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.text_model', 'gemini-2.5-flash'),
                'auto_generated' => true,
            ],
        ]);

        $post->translations()->create([
            'locale' => 'en',
            'title' => $title,
            'slug' => $englishSlug,
            'excerpt' => $englishExcerpt,
            'markdown' => $englishMarkdown,
            'meta_title' => $title,
            'meta_description' => $this->excerpt($englishMarkdown, 155),
            'seo' => ['canonical_locale' => 'en'],
        ]);

        $translationRun = $this->startRun(AiRunType::Translation, $topic, $post);
        $germanArticle = $this->promptJsonWithFallback(
            instructions: 'Translate Bitcoin magazine articles into precise, natural German. Return only valid JSON. Use real German umlauts and ß. Never leave English headings or UI-like labels untranslated unless they are proper nouns.',
            prompt: json_encode([
                'title' => $title,
                'excerpt' => $englishExcerpt,
                'blocks' => $englishBlocks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            fallback: $this->fallbackTranslatedArticle($title, $englishBlocks),
        );
        $germanTitle = $this->cleanText((string) ($germanArticle['title'] ?? $this->germanTitle($title)), $this->germanTitle($title));
        $germanBlocks = $this->sanitizeBlocks($germanArticle['blocks'] ?? null, 'de', $germanTitle, $this->fallbackMarkdown($title, 'de'));
        $germanDraft = $this->markdownFromBlocks($germanBlocks, $this->fallbackMarkdown($title, 'de'));
        $this->finishRun($translationRun, $germanDraft, ['title' => $germanTitle]);
        $germanSlug = Str::slug($germanTitle, '-', 'de');

        $post->translations()->create([
            'locale' => 'de',
            'title' => $germanTitle,
            'slug' => $germanSlug,
            'excerpt' => $this->cleanText((string) ($germanArticle['excerpt'] ?? $this->excerpt($germanDraft, 180)), $this->excerpt($germanDraft, 180)),
            'markdown' => $germanDraft,
            'meta_title' => $germanTitle,
            'meta_description' => $this->excerpt($germanDraft, 155),
            'seo' => ['canonical_locale' => 'de'],
        ]);

        $this->createBlocks($post, 'en', $englishBlocks);
        $this->createBlocks($post, 'de', $germanBlocks);

        $this->generatePostImage($post, $topic);

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
        $run = $this->startRun(AiRunType::Topic);
        $response = $this->promptWithFallback(
            instructions: 'You propose focused editorial topics for a Bitcoin sovereignty learning portal. Return one topic per line.',
            prompt: "Create {$count} evergreen article ideas about Bitcoin, financial intelligence, independence, and self custody. Avoid market predictions.",
            fallback: implode("\n", [
                'Bitcoin self custody threat models for beginners',
                'Why fiat debasement changes savings behavior',
                'How to build a personal Bitcoin treasury policy',
                'Financial independence without yield chasing',
            ]),
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
                    'category' => 'bitcoin',
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
                    ],
                ],
            ));

        $this->finishRun($run, $response, ['created_topics' => $topics->pluck('id')->all()]);

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
        if (! config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key')) {
            return $fallback;
        }

        try {
            return (string) agent($instructions)
                ->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    private function promptJsonWithFallback(string $instructions, string $prompt, array $fallback): array
    {
        if (! config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key')) {
            return $fallback;
        }

        try {
            $response = (string) agent($instructions)
                ->prompt($prompt, provider: config('magazine_ai.provider', 'gemini'), model: config('magazine_ai.text_model', 'gemini-2.5-flash'));

            $json = Str::of($response)
                ->replaceMatches('/^```(?:json)?\s*/', '')
                ->replaceMatches('/\s*```$/', '')
                ->trim()
                ->toString();

            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articleBlocks(ContentTopic $topic, string $locale, string $title, string $markdown): array
    {
        $fallback = [
            'blocks' => $this->fallbackBlocks($title, $locale, $markdown),
        ];

        $response = $this->promptJsonWithFallback(
            instructions: 'Convert an educational Bitcoin article into a premium magazine block plan. Return only valid JSON. Use these block types only: markdown, insight, checklist, flow_diagram, sketch. Do not include raw HTML or raw SVG.',
            prompt: json_encode([
                'locale' => $locale,
                'topic' => $topic->title,
                'audience_level' => $topic->audience_level,
                'brief' => $topic->brief,
                'markdown' => $markdown,
                'schema' => [
                    'blocks' => [
                        [
                            'type' => 'markdown',
                            'markdown' => 'Markdown section text',
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
            fallback: $fallback,
        );

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

        $allowedTypes = ['markdown', 'insight', 'checklist', 'flow_diagram', 'sketch'];
        $sanitized = collect($blocks)
            ->filter(fn (mixed $block): bool => is_array($block))
            ->map(function (array $block) use ($allowedTypes): array {
                $type = in_array($block['type'] ?? null, $allowedTypes, true) ? $block['type'] : 'markdown';
                $data = is_array($block['data'] ?? null) ? $this->sanitizeBlockData($block['data']) : [];

                return [
                    'type' => $type,
                    'markdown' => $type === 'markdown' ? $this->cleanMarkdown($block['markdown'] ?? null) : null,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $block): bool => $block['type'] !== 'markdown' || filled($block['markdown']))
            ->take(8)
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
            ->pluck('markdown')
            ->filter(fn (mixed $markdown): bool => filled($markdown))
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
                'markdown' => $block['markdown'],
                'data' => $block['data'],
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
            'prompt_version' => 1,
            'no_unsplash' => true,
        ];

        if (! config('ai.providers.'.config('magazine_ai.provider', 'gemini').'.key')) {
            $post->assets()->create([
                'type' => 'image',
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty cover for {$topic->title}",
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

            $path = $image->storePubliclyAs("post-assets/{$post->id}", "{$post->slug}.png", 'public');

            $post->assets()->create([
                'type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'url' => is_string($path) ? Storage::disk('public')->url($path) : null,
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty cover for {$topic->title}",
                'status' => is_string($path) ? 'ready' : 'pending',
                'metadata' => $metadata,
            ]);

            $this->finishRun($run, 'Image generated and stored.', $metadata);
        } catch (Throwable $exception) {
            $post->assets()->create([
                'type' => 'image',
                'locale' => 'en',
                'provider' => config('magazine_ai.provider', 'gemini'),
                'model' => config('magazine_ai.image_model', 'gemini-2.5-flash-image'),
                'prompt' => $prompt,
                'alt_text' => "Synthwave cypherpunk Bitcoin sovereignty cover for {$topic->title}",
                'status' => 'pending',
                'metadata' => $metadata + ['error' => $exception->getMessage()],
            ]);

            $this->finishRun($run, $exception->getMessage(), $metadata + ['failed' => true]);
        }
    }

    private function synthwaveImagePrompt(ContentTopic $topic): string
    {
        return "Premium synthwave editorial header image for article topic: {$topic->title}. Audience level: {$topic->audience_level}. Bitcoin financial sovereignty context, dark readable magazine composition, Bitcoin orange focal light, restrained neon cyan and magenta accents, subtle grid lines, abstract ledger details, no text in image, no logos, no real people, no stock-photo look.";
    }

    private function startRun(AiRunType $type, ?ContentTopic $topic = null, ?Post $post = null, ?string $model = null): AiRun
    {
        return AiRun::create([
            'content_topic_id' => $topic?->id,
            'post_id' => $post?->id,
            'type' => $type,
            'status' => AiRunStatus::Running,
            'provider' => config('magazine_ai.provider', 'gemini'),
            'model' => $model ?? config('magazine_ai.text_model', 'gemini-2.5-flash'),
            'started_at' => now(),
        ]);
    }

    private function finishRun(AiRun $run, string $response, array $output = []): void
    {
        $run->update([
            'status' => AiRunStatus::Succeeded,
            'response' => $response,
            'output' => $output,
            'finished_at' => now(),
        ]);
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
        if ($locale === 'de') {
            return [
                [
                    'type' => 'markdown',
                    'markdown' => $markdown,
                    'data' => [],
                ],
                [
                    'type' => 'insight',
                    'markdown' => null,
                    'data' => [
                        'title' => 'Kernaussage',
                        'body' => 'Souveränität entsteht nicht durch Tempo, sondern durch klare Regeln, kleine Tests und wiederholbare Entscheidungen.',
                    ],
                ],
                [
                    'type' => 'flow_diagram',
                    'markdown' => null,
                    'data' => [
                        'title' => 'Entscheidungspfad',
                        'steps' => ['Ziel klären', 'Risiko bewerten', 'Verwahrung testen', 'Regelmäßig prüfen'],
                    ],
                ],
                [
                    'type' => 'checklist',
                    'markdown' => null,
                    'data' => [
                        'title' => 'Prüfliste',
                        'items' => ['Zeithorizont notieren', 'Seed-Aufbewahrung planen', 'Kleine Testtransaktion senden', 'Dokumentation aktualisieren'],
                    ],
                ],
            ];
        }

        return [
            [
                'type' => 'markdown',
                'markdown' => $markdown,
                'data' => [],
            ],
            [
                'type' => 'insight',
                'markdown' => null,
                'data' => [
                    'title' => 'Core insight',
                    'body' => 'Sovereignty improves when decisions are explicit, tested at small scale, and reviewed on a schedule.',
                ],
            ],
            [
                'type' => 'flow_diagram',
                'markdown' => null,
                'data' => [
                    'title' => 'Decision path',
                    'steps' => ['Clarify goal', 'Map risk', 'Test custody', 'Review regularly'],
                ],
            ],
            [
                'type' => 'checklist',
                'markdown' => null,
                'data' => [
                    'title' => 'Field checklist',
                    'items' => ['Write the time horizon', 'Plan seed storage', 'Send a small test transaction', 'Update the policy note'],
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

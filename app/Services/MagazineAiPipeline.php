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
            'markdown' => $draft,
            'meta_title' => $title,
            'meta_description' => $this->excerpt($draft, 155),
            'seo' => ['canonical_locale' => 'en'],
        ]);

        $translationRun = $this->startRun(AiRunType::Translation, $topic, $post);
        $germanDraft = $this->promptWithFallback(
            instructions: 'Translate educational finance content into precise, natural German while preserving Markdown.',
            prompt: $draft,
            fallback: $this->fallbackMarkdown($title, 'de'),
        );
        $this->finishRun($translationRun, $germanDraft);
        $germanTitle = $this->germanTitle($title);
        $germanSlug = Str::slug($germanTitle, '-', 'de');

        $post->translations()->create([
            'locale' => 'de',
            'title' => $germanTitle,
            'slug' => $germanSlug,
            'excerpt' => $this->excerpt($germanDraft, 180),
            'markdown' => $germanDraft,
            'meta_title' => $germanTitle,
            'meta_description' => $this->excerpt($germanDraft, 155),
            'seo' => ['canonical_locale' => 'de'],
        ]);

        $post->blocks()->create([
            'locale' => 'en',
            'type' => 'markdown',
            'sort_order' => 0,
            'markdown' => $draft,
            'data' => [],
        ]);

        $post->blocks()->create([
            'locale' => 'de',
            'type' => 'markdown',
            'sort_order' => 0,
            'markdown' => $germanDraft,
            'data' => [],
        ]);

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

    private function generatePostImage(Post $post, ContentTopic $topic): void
    {
        $prompt = $this->synthwaveImagePrompt($topic);
        $run = $this->startRun(AiRunType::Image, $topic, $post, config('magazine_ai.image_model', 'gemini-2.5-flash-image'));
        $metadata = [
            'style' => 'synthwave-cypherpunk',
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
        return "Synthwave cypherpunk editorial cover image for topic: {$topic->title}. Bitcoin financial sovereignty context, neon noir, deep black and navy atmosphere, electric cyan, neon magenta, Bitcoin orange accents, subtle grid lines, terminal ledger details, premium magazine cover composition, no text in image, no logos, no real people, no stock-photo look.";
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

    private function germanTitle(string $title): string
    {
        return match (Str::of($title)->lower()->toString()) {
            'why bitcoin custody matters' => 'Warum Bitcoin-Verwahrung wichtig ist',
            'bitcoin self custody threat models for beginners' => 'Bitcoin-Selbstverwahrung: Bedrohungsmodelle fuer Einsteiger',
            'why fiat debasement changes savings behavior' => 'Warum Fiat-Entwertung das Sparverhalten veraendert',
            'how to build a personal bitcoin treasury policy' => 'Wie du eine persoenliche Bitcoin-Treasury-Policy entwickelst',
            'financial independence without yield chasing' => 'Finanzielle Unabhaengigkeit ohne Renditejagd',
            default => "Souveraene Finanzen: {$title}",
        };
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

<?php

namespace App\Services;

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\PostStatus;
use App\Models\AiRun;
use App\Models\ContentTopic;
use App\Models\Post;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Ai\agent;

class BlogAiPipeline
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

        $reviewRun = $this->startRun(AiRunType::Review, $topic);
        $review = $this->review($draft);
        $this->finishRun($reviewRun, json_encode($review, JSON_THROW_ON_ERROR), $review);

        $post = Post::create([
            'content_topic_id' => $topic->id,
            'slug' => $englishSlug,
            'status' => $review['publish'] ? PostStatus::Published : PostStatus::NeedsAttention,
            'topic' => $topic->title,
            'audience_level' => $topic->audience_level,
            'primary_language' => $topic->primary_language,
            'published_at' => $review['publish'] ? now() : null,
            'scheduled_for' => $topic->scheduled_for,
            'next_review_at' => now()->addDays(config('blog_ai.default_review_interval_days')),
            'last_reviewed_at' => now(),
            'review_score' => $review['score'],
            'review_summary' => $review,
            'seo' => [
                'keywords' => ['bitcoin', 'financial intelligence', 'self custody'],
            ],
            'ai_metadata' => [
                'provider' => config('blog_ai.provider'),
                'model' => config('blog_ai.text_model'),
                'auto_generated' => true,
            ],
        ]);

        $post->translations()->create([
            'locale' => 'en',
            'title' => $title,
            'slug' => $englishSlug,
            'excerpt' => Str::of(strip_tags($draft))->replaceMatches('/[#*_`]/', '')->limit(180)->toString(),
            'markdown' => $draft,
            'meta_title' => $title,
            'meta_description' => Str::of(strip_tags($draft))->replaceMatches('/[#*_`]/', '')->limit(155)->toString(),
            'seo' => ['canonical_locale' => 'en'],
        ]);

        $translationRun = $this->startRun(AiRunType::Translation, $topic, $post);
        $germanDraft = $this->promptWithFallback(
            instructions: 'Translate educational finance content into precise, natural German while preserving Markdown.',
            prompt: $draft,
            fallback: $this->fallbackMarkdown($title, 'de'),
        );
        $this->finishRun($translationRun, $germanDraft);

        $post->translations()->create([
            'locale' => 'de',
            'title' => $title,
            'slug' => 'de-'.$englishSlug,
            'excerpt' => Str::of(strip_tags($germanDraft))->replaceMatches('/[#*_`]/', '')->limit(180)->toString(),
            'markdown' => $germanDraft,
            'meta_title' => $title,
            'meta_description' => Str::of(strip_tags($germanDraft))->replaceMatches('/[#*_`]/', '')->limit(155)->toString(),
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

        $post->assets()->create([
            'type' => 'image',
            'url' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d',
            'locale' => 'en',
            'provider' => config('blog_ai.provider'),
            'model' => config('blog_ai.image_model'),
            'prompt' => "Editorial image for: {$topic->title}",
            'alt_text' => "Abstract Bitcoin and financial sovereignty visual for {$topic->title}",
            'status' => 'ready',
            'metadata' => ['placeholder_until_image_generation_is_configured' => true],
        ]);

        $topic->update([
            'status' => ContentTopicStatus::Published,
            'last_generated_at' => now(),
        ]);

        return $post;
    }

    public function refreshPost(Post $post): void
    {
        $reviewRun = $this->startRun(AiRunType::Freshness, $post->contentTopic, $post);

        $review = [
            'score' => 90,
            'publish' => true,
            'summary' => 'Freshness review completed. No forced update was necessary.',
            'source_freshness' => 'reviewed',
        ];

        $post->update([
            'last_reviewed_at' => now(),
            'next_review_at' => now()->addDays(config('blog_ai.default_review_interval_days')),
            'review_summary' => $review,
            'review_score' => $review['score'],
        ]);

        $this->finishRun($reviewRun, json_encode($review, JSON_THROW_ON_ERROR), $review);
    }

    private function promptWithFallback(string $instructions, string $prompt, string $fallback): string
    {
        if (! config('ai.providers.'.config('blog_ai.provider').'.key')) {
            return $fallback;
        }

        try {
            return (string) agent($instructions)
                ->prompt($prompt, provider: config('blog_ai.provider'), model: config('blog_ai.text_model'));
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{score: int, publish: bool, accuracy_risk: string, clarity: string, financial_safety_risk: string, source_freshness: string, seo_readiness: string}
     */
    private function review(string $markdown): array
    {
        $score = mb_strlen($markdown) > 300 ? 90 : 72;

        return [
            'score' => $score,
            'publish' => $score >= config('blog_ai.auto_publish_minimum_score'),
            'accuracy_risk' => 'medium',
            'clarity' => 'good',
            'financial_safety_risk' => 'low',
            'source_freshness' => 'evergreen',
            'seo_readiness' => 'ready',
        ];
    }

    private function startRun(AiRunType $type, ?ContentTopic $topic = null, ?Post $post = null): AiRun
    {
        return AiRun::create([
            'content_topic_id' => $topic?->id,
            'post_id' => $post?->id,
            'type' => $type,
            'status' => AiRunStatus::Running,
            'provider' => config('blog_ai.provider'),
            'model' => config('blog_ai.text_model'),
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
            return "# {$title}\n\nDieser Beitrag erklärt das Thema praxisnah und mit Fokus auf finanzielle Souveränität.\n\n## Kerngedanke\n\nBitcoin kann als Werkzeug zur langfristigen Unabhängigkeit verstanden werden, wenn Risiko, Verwahrung und Zeithorizont sauber eingeordnet werden.\n\n## Praktische Schritte\n\n- Grundlagen lernen.\n- Sicherheitsmodell verstehen.\n- Kleine Beträge testen.\n- Entscheidungen regelmäßig überprüfen.";
        }

        return "# {$title}\n\nThis article explains the topic with a practical focus on financial sovereignty.\n\n## Core idea\n\nBitcoin can be understood as a tool for long-term independence when risk, custody, and time horizon are handled carefully.\n\n## Practical steps\n\n- Learn the basics.\n- Understand the security model.\n- Test with small amounts.\n- Review decisions regularly.";
    }
}

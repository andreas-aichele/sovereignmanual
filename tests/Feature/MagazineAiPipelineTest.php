<?php

use App\Enums\AiRunStatus;
use App\Enums\AiRunType;
use App\Enums\ContentTopicStatus;
use App\Enums\ContentType;
use App\Enums\Language;
use App\Enums\PostStatus;
use App\Jobs\GeneratePostFromTopic;
use App\Jobs\IdeateNewsTopicsJob;
use App\Models\AiRun;
use App\Models\Category;
use App\Models\ContentTopic;
use App\Models\Pillar;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Services\MagazineAiPipeline;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    config(['magazine_ai.allow_fallback_publication' => true]);
});

function newsCategory(): Category
{
    return Category::factory()->create([
        'key' => 'news',
        'lang' => Language::German,
        'slug' => 'news',
        'name' => 'News',
    ]);
}

function digitalSovereigntyCategory(): Category
{
    $englishPillar = Pillar::query()->updateOrCreate(
        [
            'key' => 'digital-sovereignty',
            'lang' => Language::English,
        ],
        [
            'slug' => 'digital-sovereignty',
            'name' => 'Digital Sovereignty',
            'description' => 'Clear guidance for protecting privacy and retaining control over personal data.',
        ],
    );
    $germanPillar = Pillar::query()->updateOrCreate(
        [
            'key' => 'digital-sovereignty',
            'lang' => Language::German,
        ],
        [
            'slug' => 'digitale-souveraenitaet',
            'name' => 'Digitale Souveränität',
            'description' => 'Klare Orientierung für Privatsphäre, sichere Werkzeuge und die Kontrolle über eigene Daten.',
        ],
    );

    Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::English,
        'pillar_id' => $englishPillar->id,
        'slug' => 'privacy-security',
        'name' => 'Privacy & Security',
        'description' => 'Practical guidance for privacy and security.',
    ]);

    return Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'pillar_id' => $germanPillar->id,
        'slug' => 'privatsphaere-sicherheit',
        'name' => 'Privatsphäre & Sicherheit',
        'description' => 'Praktische Orientierung für Privatsphäre und Sicherheit.',
    ]);
}

function bitcoinBriefingCategory(): Category
{
    return Category::factory()->create([
        'key' => 'economics',
        'lang' => Language::German,
        'slug' => 'oekonomie',
        'name' => 'Ökonomie',
    ]);
}

/**
 * @return array<string, mixed>
 */
function credibleBriefingResearch(): array
{
    return [
        'news_research' => [
            'summary' => 'A documented Bitcoin protocol update.',
            'sources' => [
                [
                    'title' => 'Bitcoin Core release notes',
                    'url' => 'https://bitcoincore.org/en/releases/example/',
                    'published_at' => now()->toDateString(),
                    'publisher' => 'Bitcoin Core',
                    'type' => 'primary',
                    'credibility_note' => 'Primary project source.',
                ],
                [
                    'title' => 'GitHub release',
                    'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                    'published_at' => now()->toDateString(),
                    'publisher' => 'GitHub',
                    'type' => 'technical',
                    'credibility_note' => 'Technical release artifact.',
                ],
            ],
            'credibility_notes' => ['Two independent credible sources confirm the update.'],
            'open_questions' => ['Deployment timing varies by user.'],
            'grounding_citations' => [
                [
                    'title' => 'Bitcoin Core release notes',
                    'url' => 'https://bitcoincore.org/en/releases/example/',
                ],
                [
                    'title' => 'GitHub release',
                    'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                ],
            ],
        ],
    ];
}

/**
 * @return Collection<int, ContentTopic>
 */
function newsTopicsFromResearch(array $research, int $count = 1): Collection
{
    $category = bitcoinBriefingCategory();
    $research['topics'] = collect($research['topics'] ?? [])
        ->map(fn (mixed $topic): mixed => is_array($topic)
            ? $topic + ['category_key' => $category->key]
            : $topic)
        ->all();

    return (new ReflectionMethod(MagazineAiPipeline::class, 'createNewsTopicsFromResearch'))
        ->invoke(app(MagazineAiPipeline::class), $research, collect([$category->key => $category]), $count, []);
}

test('pipeline creates a published post from a German source with English translation', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);
    $createdStatus = null;
    $createdPublishedAt = null;

    Post::created(function (Post $post) use (&$createdStatus, &$createdPublishedAt): void {
        $createdStatus = $post->status;
        $createdPublishedAt = $post->published_at;
    });

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Warum Selbstverwahrung zählt',
        'primary_language' => 'de',
        'target_languages' => ['en'],
        'content_type' => ContentType::Guide,
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();
    $asset = $post->assets()->firstOrFail();

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->primary_language)->toBe('de')
        ->and($post->content_type)->toBe(ContentType::Guide)
        ->and($createdStatus)->toBe(PostStatus::Draft)
        ->and($createdPublishedAt)->toBeNull()
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and(mb_strlen($englishTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($englishTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($englishTranslation->excerpt)->toBe($englishTranslation->meta_description)
        ->and($englishTranslation->excerpt)->not->toEndWith('...')
        ->and($englishTranslation->seo['keywords'])->toContain('guide')
        ->and($englishTranslation->slug)->toBe('warum-selbstverwahrung-zahlt')
        ->and($germanTranslation->title)->toBe('Warum Selbstverwahrung zählt')
        ->and($germanTranslation->markdown)->toContain('Dieser Artikel ordnet das Thema praktisch ein')
        ->and($germanTranslation->slug)->toBe('warum-selbstverwahrung-zaehlt')
        ->and(mb_strlen($germanTranslation->meta_title))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($germanTranslation->meta_description))->toBeLessThanOrEqual(160)
        ->and($germanTranslation->excerpt)->toBe($germanTranslation->meta_description)
        ->and($germanTranslation->excerpt)->not->toEndWith('...')
        ->and($germanTranslation->seo['keywords'])->toContain('guide')
        ->and($post->category?->key)->toBe('self-custody')
        ->and($post->blocks()->count())->toBe(0)
        ->and($asset->url)->toBeNull()
        ->and($asset->metadata['role'])->toBe('header')
        ->and($asset->metadata['reason'])->toBe('image_generation_not_configured')
        ->and($asset->metadata['style'])->toBe('editorial-documentary')
        ->and($asset->metadata['prompt_version'])->toBe(3)
        ->and($asset->alt_text)->toBe('Header image for the article Warum Selbstverwahrung zählt.')
        ->and($asset->alt_text)->not->toContain('Synthwave')
        ->and($asset->metadata['alt_texts']['en'])->toBe('Header image for the article Warum Selbstverwahrung zählt.')
        ->and($asset->metadata['alt_texts']['de'])->toBe('Header image for the article Warum Selbstverwahrung zählt.')
        ->and($asset->prompt)->toContain('Full-bleed documentary editorial header image')
        ->and($asset->prompt)->toContain('Calm, human-scale visual language')
        ->and($asset->prompt)->toContain('No neon, cyberpunk')
        ->and($asset->prompt)->toContain('glossy AI aesthetic')
        ->and($asset->prompt)->toContain('stock-photo look')
        ->and($asset->prompt)->not->toContain('unsplash')
        ->and($asset->prompt)->not->toContain('synthwave')
        ->and($post->aiRuns()->count())->toBeGreaterThanOrEqual(1)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Published);
});

test('pipeline refuses to publish a fallback article without an AI provider by default', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.allow_fallback_publication' => false,
    ]);
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Eine sichere Dokumentationsroutine',
    ]);

    expect(fn () => app(MagazineAiPipeline::class)->generatePost($topic))
        ->toThrow(RuntimeException::class, 'A configured AI provider is required to publish');

    expect(Post::query()->count())->toBe(0)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Scheduled);
});

test('pipeline creates translations for languages defined in the language enum', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Warum Selbstverwahrung zählt',
        'primary_language' => 'de',
        'target_languages' => ['en'],
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);

    expect($post->primary_language)->toBe('de')
        ->and($post->translations()->pluck('locale')->sort()->values()->all())->toBe(['de', 'en'])
        ->and($post->blocks()->count())->toBe(0)
        ->and($post->aiRuns()->where('type', AiRunType::Translation)->exists())->toBeTrue();
});

test('pipeline no longer appends related reading blocks to new articles', function () {
    config(['ai.providers.gemini.key' => null]);

    $existingPost = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $existingPost->id,
        'locale' => 'en',
        'title' => 'Bitcoin wallet backups',
        'slug' => 'bitcoin-wallet-backups',
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();

    expect($englishTranslation->markdown)
        ->not->toContain('Related reading')
        ->not->toContain('[Bitcoin wallet backups]')
        ->and($englishTranslation->seo['internal_links'][0]['slug'])->toBe('bitcoin-wallet-backups');
    expect($post->blocks()->where('heading', 'Related reading')->exists())->toBeFalse();
});

test('pipeline keeps h1 title fallback within limits without cutting words', function () {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin self custody threat models for beginners who want practical security without relying on custodians',
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $englishTranslation = $post->translations()->where('locale', 'en')->firstOrFail();

    expect(mb_strlen($englishTranslation->title))->toBeLessThanOrEqual(70)
        ->and($englishTranslation->markdown)->toContain('# Bitcoin self custody threat models for beginners who want practical security without relying on custodians')
        ->and($englishTranslation->title)->toEndWith('practical')
        ->and($englishTranslation->title)->not->toEndWith('practic');
});

test('pipeline retries seo title generation until length requirements pass', function () {
    $pipeline = file_get_contents(app_path('Services/MagazineAiPipeline.php'));

    expect($pipeline)->toContain('Generate the visible H1 article_title, browser/search meta_title, and meta_description directly at the correct length')
        ->and($pipeline)->toContain('Do not return overlong text for PHP to shorten later')
        ->and($pipeline)->toContain('Never end meta_description with an ellipsis')
        ->and($pipeline)->toContain('meta_description must be complete and must not end with an ellipsis')
        ->and($pipeline)->toContain('previous_attempt_feedback')
        ->and($pipeline)->toContain('$problems = []')
        ->and($pipeline)->toContain('for ($attempt = 1; $attempt <= 3; $attempt++)');
});

test('pipeline fallback translations are created for supported locales without locale-specific copy', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Einfache Bedrohungsanalyse für den digitalen Alltag',
        'primary_language' => 'de',
        'target_languages' => ['en'],
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);
    $germanTranslation = $post->translations()->where('locale', 'de')->firstOrFail();

    expect($post->primary_language)->toBe('de')
        ->and($germanTranslation->title)->toBe('Einfache Bedrohungsanalyse für den digitalen Alltag')
        ->and($post->blocks()->count())->toBe(0)
        ->and($germanTranslation->markdown)->toContain('Einfache Bedrohungsanalyse für den digitalen Alltag');
});

test('evergreen ideation uses German as the primary editorial locale by default', function () {
    config(['ai.providers.gemini.key' => null]);
    Category::query()->delete();

    $category = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'slug' => 'privatsphaere-sicherheit',
        'name' => 'Privatsphäre & Sicherheit',
        'description' => 'Praktische Orientierung für digitale Privatsphäre und Sicherheit.',
    ]);

    $topics = app(MagazineAiPipeline::class)->createTopicIdeas(1);

    expect(config('magazine_ai.primary_locale'))->toBe('de')
        ->and($topics)->toHaveCount(1)
        ->and($topics->first()?->category_id)->toBe($category->id)
        ->and($topics->first()?->primary_language)->toBe('de')
        ->and($topics->first()?->target_languages)->toBe(['en'])
        ->and($topics->first()?->content_type)->toBe(ContentType::Guide);
});

test('topic and briefing ideation explicitly require German source output', function () {
    $pipeline = file_get_contents(app_path('Services/MagazineAiPipeline.php'));

    expect($pipeline)->toContain('Return one topic per line in German only')
        ->and($pipeline)->toContain('Return titles, summaries, credibility notes, and open questions in German only')
        ->and($pipeline)->toContain("'output_language' => 'German'");
});

test('pipeline persists every supported content type on the generated post', function (ContentType $contentType) {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => "A practical {$contentType->value} for independent routines",
        'primary_language' => 'de',
        'target_languages' => ['en'],
        'content_type' => $contentType,
        'constraints' => $contentType === ContentType::Briefing
            ? credibleBriefingResearch()
            : ['tone' => 'clear, practical, non-hype'],
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);

    expect($post->content_type)->toBe($contentType)
        ->and($post->refresh()->content_type)->toBe($contentType)
        ->and($post->contentTopic?->content_type)->toBe($contentType);
})->with([
    'guide' => [ContentType::Guide],
    'checklist' => [ContentType::Checklist],
    'analysis' => [ContentType::Analysis],
    'briefing' => [ContentType::Briefing],
]);

test('pipeline puts localized pillar, category, and content type context into editorial prompts', function () {
    $category = digitalSovereigntyCategory();
    $topic = ContentTopic::factory()->due()->create([
        'category_id' => $category->id,
        'title' => 'Eine sichere Kommunikationsroutine dokumentieren',
        'primary_language' => 'de',
        'target_languages' => ['en'],
        'content_type' => ContentType::Analysis,
    ]);
    $pipeline = app(MagazineAiPipeline::class);

    $draftPrompt = json_decode(
        (new ReflectionMethod(MagazineAiPipeline::class, 'draftPrompt'))->invoke($pipeline, $topic, []),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $draftInstructions = (new ReflectionMethod(MagazineAiPipeline::class, 'draftInstructions'))->invoke($pipeline, $topic);
    $imagePrompt = (new ReflectionMethod(MagazineAiPipeline::class, 'editorialImagePrompt'))->invoke($pipeline, $topic);

    expect($draftPrompt['editorial_context']['pillar']['key'])->toBe('digital-sovereignty')
        ->and($draftPrompt['editorial_context']['pillar']['name'])->toBe('Digitale Souveränität')
        ->and($draftPrompt['editorial_context']['category']['key'])->toBe('privacy-security')
        ->and($draftPrompt['editorial_context']['category']['name'])->toBe('Privatsphäre & Sicherheit')
        ->and($draftPrompt['editorial_context']['content_type'])->toBe(ContentType::Analysis->value)
        ->and($draftInstructions)->toContain('"key":"digital-sovereignty"')
        ->and($draftInstructions)->toContain('"content_type":"analysis"')
        ->and($imagePrompt)->toContain('"name":"Digitale Souveränität"')
        ->and($imagePrompt)->toContain('"content_type":"analysis"');
});

test('briefing posts require two independent credible sources and persist structured sources', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $research = credibleBriefingResearch();
    $topic = ContentTopic::factory()->due()->create([
        'category_id' => newsCategory()->id,
        'title' => 'Bitcoin Core veröffentlicht ein Sicherheitsupdate',
        'primary_language' => 'de',
        'target_languages' => ['en'],
        'content_type' => ContentType::Briefing,
        'constraints' => $research,
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);

    expect($post->content_type)->toBe(ContentType::Briefing)
        ->and($post->sources)->toHaveCount(2)
        ->and($post->sources[0])->toBe($research['news_research']['sources'][0])
        ->and($post->sources[1])->toBe($research['news_research']['sources'][1]);
});

test('briefing generation only receives and cites its verified persistent source set', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $research = credibleBriefingResearch();
    $research['news_research']['sources'][] = [
        'title' => 'Invented source',
        'url' => 'https://unverified-source.example/report',
        'published_at' => now()->toDateString(),
        'publisher' => 'Unverified Source',
        'type' => 'primary',
        'credibility_note' => 'This source is not supported by grounding citations.',
    ];
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin Core veröffentlicht ein Sicherheitsupdate',
        'content_type' => ContentType::Briefing,
        'constraints' => $research,
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $verifiedSources = (new ReflectionMethod(MagazineAiPipeline::class, 'briefingSources'))
        ->invoke($pipeline, $topic);
    $draftPrompt = json_decode(
        (new ReflectionMethod(MagazineAiPipeline::class, 'draftPrompt'))
            ->invoke($pipeline, $topic, [], $verifiedSources),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($verifiedSources)->toHaveCount(2)
        ->and($draftPrompt['research']['verified_sources'])->toBe($verifiedSources)
        ->and(json_encode($draftPrompt, JSON_THROW_ON_ERROR))->not->toContain('unverified-source.example');

    $sourceGuard = new ReflectionMethod(MagazineAiPipeline::class, 'ensureGeneratedBriefingSourcesArePersisted');
    $sourceGuard->invoke(
        $pipeline,
        $topic,
        'The verified release notes are available at https://bitcoincore.org/en/releases/example/.',
        'draft',
        $verifiedSources,
    );
    $sourceGuard->invoke(
        $pipeline,
        $topic,
        'Laut Bitcoin Core enthält das Update wichtige Sicherheitskorrekturen.',
        'draft',
        $verifiedSources,
    );

    $run = AiRun::factory()->create([
        'content_topic_id' => $topic->id,
        'type' => AiRunType::Draft,
        'status' => AiRunStatus::Running,
        'finished_at' => null,
    ]);

    expect(fn () => $sourceGuard->invoke(
        $pipeline,
        $topic,
        'A fabricated report is available at unverified-source.example/report.',
        'draft',
        $verifiedSources,
        null,
        $run,
    ))->toThrow(RuntimeException::class, 'Generated briefing content references a source outside the verified source set.');

    expect($topic->refresh()->status)->toBe(ContentTopicStatus::Archived)
        ->and($run->refresh()->status)->toBe(AiRunStatus::Failed)
        ->and($run->output['stage'])->toBe('draft');
});

test('briefing generation rejects an unverified named source without a URL', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Bitcoin Core veröffentlicht ein Sicherheitsupdate',
        'content_type' => ContentType::Briefing,
        'constraints' => credibleBriefingResearch(),
    ]);
    $pipeline = app(MagazineAiPipeline::class);
    $verifiedSources = (new ReflectionMethod(MagazineAiPipeline::class, 'briefingSources'))
        ->invoke($pipeline, $topic);
    $run = AiRun::factory()->create([
        'content_topic_id' => $topic->id,
        'type' => AiRunType::Draft,
        'status' => AiRunStatus::Running,
        'finished_at' => null,
    ]);
    $sourceGuard = new ReflectionMethod(MagazineAiPipeline::class, 'ensureGeneratedBriefingSourcesArePersisted');

    expect(fn () => $sourceGuard->invoke(
        $pipeline,
        $topic,
        'Laut dem Deutschen Bitcoin-Institut ist das Update besonders relevant.',
        'draft',
        $verifiedSources,
        null,
        $run,
    ))->toThrow(RuntimeException::class, 'Generated briefing content references a source outside the verified source set.');

    expect($topic->refresh()->status)->toBe(ContentTopicStatus::Archived)
        ->and($run->refresh()->status)->toBe(AiRunStatus::Failed)
        ->and($run->output['stage'])->toBe('draft');
});

test('pipeline archives topics that violate the editorial safeguards', function (string $title) {
    config(['ai.providers.gemini.key' => null]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => $title,
    ]);

    expect(fn () => app(MagazineAiPipeline::class)->generatePost($topic))
        ->toThrow(RuntimeException::class, 'The topic falls outside Sovereign Manual editorial safeguards.');

    expect($topic->refresh()->status)->toBe(ContentTopicStatus::Archived)
        ->and(Post::query()->count())->toBe(0);
})->with([
    'altcoin' => ['How an altcoin can replace Bitcoin'],
    'Ethereum' => ['How Ethereum fits into a digital sovereignty routine'],
    'crypto speculation' => ['A DeFi staking strategy for crypto returns'],
    'trading' => ['A trading routine for volatile markets'],
    'financial advice' => ['Individual financial advice for your savings'],
    'prepper' => ['A prepper checklist for a crisis'],
]);

test('pipeline archives generated concrete altcoin and crypto speculation content before publication', function (string $content) {
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'A practical documentation routine',
    ]);
    $post = Post::factory()->create([
        'content_topic_id' => $topic->id,
        'status' => PostStatus::Draft,
    ]);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'ensureGeneratedContentIsAllowed');

    expect(fn () => $method->invoke(
        app(MagazineAiPipeline::class),
        $topic,
        $content,
        'draft',
        $post,
    ))->toThrow(RuntimeException::class, 'Generated content falls outside Sovereign Manual editorial safeguards.');

    expect($topic->refresh()->status)->toBe(ContentTopicStatus::Archived)
        ->and(Post::query()->whereKey($post->id)->exists())->toBeFalse();
})->with([
    'Ethereum' => ['Ethereum offers an alternative to Bitcoin.'],
    'crypto speculation' => ['A DeFi staking strategy promises recurring returns.'],
]);

test('pipeline archives generated output that violates editorial safeguards before publication', function () {
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'A practical documentation routine',
    ]);
    $post = Post::factory()->create([
        'content_topic_id' => $topic->id,
        'status' => PostStatus::Draft,
    ]);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'ensureGeneratedContentIsAllowed');

    expect(fn () => $method->invoke(
        app(MagazineAiPipeline::class),
        $topic,
        'Individuelle Finanzberatung für deine Ersparnisse.',
        'draft',
        $post,
    ))->toThrow(RuntimeException::class, 'Generated content falls outside Sovereign Manual editorial safeguards.');

    expect($topic->refresh()->status)->toBe(ContentTopicStatus::Archived)
        ->and(Post::query()->whereKey($post->id)->exists())->toBeFalse();
});

test('pipeline marks the active run as failed when generated output is blocked', function () {
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'A practical documentation routine',
    ]);
    $run = AiRun::factory()->create([
        'content_topic_id' => $topic->id,
        'type' => AiRunType::Draft,
        'status' => AiRunStatus::Running,
        'finished_at' => null,
    ]);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'ensureGeneratedContentIsAllowed');

    expect(fn () => $method->invoke(
        app(MagazineAiPipeline::class),
        $topic,
        'Individuelle Finanzberatung für deine Ersparnisse.',
        'draft',
        null,
        $run,
    ))->toThrow(RuntimeException::class);

    expect($run->refresh()->status)->toBe(AiRunStatus::Failed)
        ->and($run->output['stage'])->toBe('draft');
});

test('pipeline migrates due legacy English topics to the configured German source locale', function () {
    config([
        'ai.providers.gemini.key' => null,
        'magazine_ai.primary_locale' => 'de',
    ]);

    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Eine alte Themenplanung',
        'primary_language' => 'en',
        'target_languages' => ['de'],
    ]);

    $post = app(MagazineAiPipeline::class)->generatePost($topic);

    expect($post->primary_language)->toBe('de')
        ->and($topic->refresh()->primary_language)->toBe('de')
        ->and($topic->target_languages)->toBe(['en'])
        ->and($post->translations()->where('locale', 'de')->exists())->toBeTrue()
        ->and($post->translations()->where('locale', 'en')->exists())->toBeTrue();
});

test('pipeline block planning preserves article detail up to twelve blocks', function () {
    $pipeline = file_get_contents(app_path('Services/MagazineAiPipeline.php'));

    expect($pipeline)->toContain('Preserve the full article detail')
        ->and($pipeline)->toContain('Do not summarize, shorten, or omit practical examples')
        ->and($pipeline)->toContain('Split the full draft into section blocks with several paragraphs each')
        ->and($pipeline)->toContain('does not contain nested H2 headings')
        ->and($pipeline)->toContain('Place visual/support blocks immediately after the section they clarify')
        ->and($pipeline)->toContain('do not group insight, checklist, flow_diagram, or sketch blocks at the end')
        ->and($pipeline)->toContain('->take(12)');
});

test('pipeline accepts a real generated block plan without inventing fallback blocks', function () {
    $pipeline = app(MagazineAiPipeline::class);
    $method = new ReflectionMethod(MagazineAiPipeline::class, 'sanitizeBlocks');

    $blocks = $method->invoke($pipeline, [
        [
            'type' => 'section',
            'heading' => 'Why UTXO Labeling Matters',
            'anchor' => 'why-utxo-labeling-matters',
            'markdown' => "Every time you receive Bitcoin, you receive a UTXO.\n\n```\n[KYC UTXO] --> [Combined Transaction]\n```\n\nThe diagram must arrive as its own flow_diagram block.",
            'data' => [],
        ],
        [
            'type' => 'insight',
            'markdown' => null,
            'data' => [
                'title' => 'Core insight',
                'body' => 'Labels are privacy boundaries, not decoration.',
            ],
        ],
        [
            'type' => 'flow_diagram',
            'markdown' => null,
            'data' => [
                'title' => 'Decision path',
                'diagram' => [
                    'kind' => 'flowchart',
                    'direction' => 'LR',
                    'steps' => ['Clarify goal', 'Map risk', 'Test custody', 'Review regularly'],
                ],
            ],
        ],
        [
            'type' => 'checklist',
            'markdown' => null,
            'data' => [
                'title' => 'Field checklist',
                'items' => ['Label each UTXO', 'Freeze KYC coins', 'Use coin control'],
            ],
        ],
    ], 'en');

    expect($blocks)->toHaveCount(4)
        ->and(array_column($blocks, 'type'))->toBe(['section', 'insight', 'flow_diagram', 'checklist'])
        ->and($blocks[0]['heading'])->toBe('Why UTXO Labeling Matters')
        ->and($blocks[0]['anchor'])->toBe('why-utxo-labeling-matters')
        ->and($blocks[0]['markdown'])->toContain('[KYC UTXO] --> [Combined Transaction]')
        ->and($blocks[1]['data']['body'])->toBe('Labels are privacy boundaries, not decoration.')
        ->and($blocks[2]['data']['diagram']['steps'])->toBe(['Clarify goal', 'Map risk', 'Test custody', 'Review regularly'])
        ->and($blocks[3]['data']['items'])->toBe(['Label each UTXO', 'Freeze KYC coins', 'Use coin control']);
});

test('topic ideation creates scheduled topics', function () {
    config(['ai.providers.gemini.key' => null]);

    $this->artisan('app:ideate-magazine-topics --count=2')->assertSuccessful();

    expect(ContentTopic::query()->count())->toBe(2)
        ->and(ContentTopic::query()->where('status', ContentTopicStatus::Scheduled)->count())->toBe(2)
        ->and(ContentTopic::query()->where('primary_language', 'de')->count())->toBe(2)
        ->and(ContentTopic::query()->where('content_type', ContentType::Guide)->count())->toBe(2);
});

test('evergreen topic ideation chooses a non news category before creating topics', function () {
    config(['ai.providers.gemini.key' => null]);

    $privacy = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'slug' => 'privatsphaere-sicherheit',
        'name' => 'Privatsphäre & Sicherheit',
    ]);
    Category::factory()->create([
        'key' => 'news',
        'lang' => Language::German,
        'slug' => 'news',
        'name' => 'News',
    ]);
    $historicalToolsPractice = Category::factory()->create([
        'key' => 'tools-practice',
        'lang' => Language::German,
        'slug' => 'werkzeuge-praxis',
        'name' => 'Werkzeuge & Praxis',
    ]);
    Category::query()
        ->whereNotIn('id', [$privacy->id, $historicalToolsPractice->id])
        ->where('key', '!=', 'news')
        ->delete();

    $this->artisan('app:ideate-magazine-topics --count=1')->assertSuccessful();

    $topic = ContentTopic::query()
        ->where('category_id', $privacy->id)
        ->latest('id')
        ->firstOrFail();

    expect($topic->category_id)->toBe($privacy->id)
        ->and($topic->category?->key)->toBe('privacy-security')
        ->and($topic->category?->key)->not->toBe('news')
        ->and($topic->category?->key)->not->toBe('tools-practice');
});

test('topic ideation stores existing category topics as similarity exclusions', function () {
    config(['ai.providers.gemini.key' => null]);

    $category = Category::factory()->create([
        'key' => 'privacy-security',
        'lang' => Language::German,
        'slug' => 'privatsphaere-sicherheit',
        'name' => 'Privatsphäre & Sicherheit',
    ]);
    Category::query()->whereKeyNot($category->id)->delete();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);
    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Digitale Bedrohungsanalysen, die sich ständig wiederholen',
    ]);
    Category::query()->whereKeyNot($category->id)->delete();

    $this->artisan('app:ideate-magazine-topics --count=1')->assertSuccessful();

    $topic = ContentTopic::query()
        ->where('category_id', $category->id)
        ->latest('id')
        ->firstOrFail();

    expect($topic->constraints['avoid_similar_topics'])->toContain('Digitale Bedrohungsanalysen, die sich ständig wiederholen');
});

test('Bitcoin briefing ideation without provider web research fails visibly', function () {
    config(['ai.providers.gemini.key' => null]);

    $this->artisan('app:ideate-news-topics --count=1 --sync')->assertFailed();

    expect(ContentTopic::query()->count())->toBe(0)
        ->and(AiRun::query()->latest('id')->firstOrFail()->status)->toBe(AiRunStatus::Failed);
});

test('Bitcoin briefing ideation command queues by default', function () {
    Queue::fake();

    $this->artisan('app:ideate-news-topics --count=2')->assertSuccessful();

    Queue::assertPushed(IdeateNewsTopicsJob::class, fn (IdeateNewsTopicsJob $job): bool => $job->count === 2);
});

test('Bitcoin briefing research must include at least two credible independent sources', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
        'example.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
            ],
            [
                'title' => 'GitHub release',
                'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Two independent credible sources confirm the update.'],
                'open_questions' => ['Deployment timing varies by user.'],
            ],
            [
                'title' => 'Unverified Bitcoin rumor',
                'summary' => 'A rumor with weak sourcing.',
                'sources' => [
                    [
                        'title' => 'Anonymous post',
                        'url' => 'https://example.com/rumor',
                        'type' => 'supporting',
                    ],
                ],
            ],
        ],
    ], 2);

    expect($topics)->toHaveCount(1);

    $topic = $topics->first();

    expect($topic->category?->key)->toBe('economics')
        ->and($topic->primary_language)->toBe('de')
        ->and($topic->content_type)->toBe(ContentType::Briefing)
        ->and($topic->constraints['category_key'])->toBe('economics')
        ->and($topic->constraints['news_research']['sources'])->toHaveCount(2)
        ->and($topic->constraints['news_research']['grounding_citations'])->toHaveCount(2)
        ->and($topic->constraints['news_research']['credibility_notes'])->toContain('Two independent credible sources confirm the update.');
});

test('briefing topics without verified sources are not generated into posts', function () {
    config(['ai.providers.gemini.key' => null]);
    Http::fake();

    $topic = ContentTopic::factory()->due()->create([
        'category_id' => newsCategory()->id,
        'title' => 'Unverified Bitcoin news item',
        'content_type' => ContentType::Briefing,
        'constraints' => ['tone' => 'clear, sourced, non-hype'],
    ]);

    expect(fn () => app(MagazineAiPipeline::class)->generatePost($topic))
        ->toThrow(RuntimeException::class, 'Briefing topics require at least two credible independent sources');

    expect(Post::query()->count())->toBe(0)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Archived);
});

test('generation job skips invalid briefing topics after archiving them', function () {
    Http::fake();

    $topic = ContentTopic::factory()->due()->create([
        'category_id' => newsCategory()->id,
        'title' => 'Old news topic with dead sources',
        'content_type' => ContentType::Briefing,
        'constraints' => ['news_research' => ['sources' => []]],
    ]);

    (new GeneratePostFromTopic($topic))->handle(app(MagazineAiPipeline::class));

    expect(Post::query()->count())->toBe(0)
        ->and($topic->refresh()->status)->toBe(ContentTopicStatus::Archived);
});

test('news research rejects unreachable source urls', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 404),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
            ],
            [
                'title' => 'Missing GitHub release',
                'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/missing',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'Missing GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/missing',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['One source is unavailable.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(0);
});

test('news research rejects source urls that are not backed by grounding citations', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [],
        'topics' => [
            [
                'title' => 'Bitcoin Core releases a security update',
                'summary' => 'A sourced update about a Bitcoin Core release.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Sources are verified directly by URL.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(0);
});

test('news research rejects source urls that cannot be verified by the publisher', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'reputable-news.com/*' => Http::response('', 403),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [
            [
                'title' => 'Bitcoin Core release notes',
                'url' => 'https://bitcoincore.org/en/releases/example/',
            ],
            [
                'title' => 'Reputable report',
                'url' => 'https://reputable-news.com/bitcoin-core-update',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core publishes a sourced update',
                'summary' => 'A sourced update about Bitcoin Core.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'Reputable report',
                        'url' => 'https://reputable-news.com/bitcoin-core-update',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Reputable News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Independent reporting from a known publication.',
                    ],
                ],
                'credibility_notes' => ['One publisher blocks automated requests.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(0);
});

test('news research accepts independent reputable reporting without primary source', function () {
    Http::fake([
        'example-news.com/*' => Http::response('', 200),
        'another-news.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [
            [
                'title' => 'First report',
                'url' => 'https://example-news.com/bitcoin-policy',
            ],
            [
                'title' => 'Second report',
                'url' => 'https://another-news.com/bitcoin-policy',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin policy story with only secondary reporting',
                'summary' => 'A story sourced only by secondary reporting.',
                'sources' => [
                    [
                        'title' => 'First report',
                        'url' => 'https://example-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Example News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Known publication.',
                    ],
                    [
                        'title' => 'Second report',
                        'url' => 'https://another-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Another News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Independent publication.',
                    ],
                ],
                'credibility_notes' => ['Two independent publications confirm the story.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(1)
        ->and($topics->first()->constraints['news_research']['sources'])->toHaveCount(2);
});

test('news research rejects sources from the same publisher domain', function () {
    Http::fake([
        'example-news.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [],
        'topics' => [
            [
                'title' => 'Bitcoin story repeated by one publisher',
                'summary' => 'A story with two URLs from one publisher.',
                'sources' => [
                    [
                        'title' => 'First report',
                        'url' => 'https://example-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Example News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Known publication.',
                    ],
                    [
                        'title' => 'Second report',
                        'url' => 'https://example-news.com/bitcoin-policy-followup',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Example News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Same publisher follow-up.',
                    ],
                ],
                'credibility_notes' => ['Only one publisher domain is represented.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(0);
});

test('news research does not count supporting sources as credible', function () {
    Http::fake([
        'example-news.com/*' => Http::response('', 200),
        'social.example.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [],
        'topics' => [
            [
                'title' => 'Bitcoin story with weak supporting context',
                'summary' => 'A story with one credible report and one supporting source.',
                'sources' => [
                    [
                        'title' => 'Independent report',
                        'url' => 'https://example-news.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Example News',
                        'type' => 'reputable_reporting',
                        'credibility_note' => 'Known publication.',
                    ],
                    [
                        'title' => 'Social discussion',
                        'url' => 'https://social.example.com/bitcoin-policy',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Social Example',
                        'type' => 'supporting',
                        'credibility_note' => 'Useful context but not a credible source.',
                    ],
                ],
                'credibility_notes' => ['Only one credible source is present.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(0);
});

test('news research stores google redirect citations as diagnostics', function () {
    Http::fake([
        'bitcoincore.org/*' => Http::response('', 200),
        'github.com/*' => Http::response('', 200),
    ]);

    $topics = newsTopicsFromResearch([
        'grounding_citations' => [
            [
                'title' => 'bitcoincore.org',
                'url' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/bitcoin-core-release',
            ],
            [
                'title' => 'github.com',
                'url' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/github-release',
            ],
        ],
        'topics' => [
            [
                'title' => 'Bitcoin Core publishes a sourced update',
                'summary' => 'A sourced update about Bitcoin Core.',
                'sources' => [
                    [
                        'title' => 'Bitcoin Core release notes',
                        'url' => 'https://bitcoincore.org/en/releases/example/',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'Bitcoin Core',
                        'type' => 'primary',
                        'credibility_note' => 'Primary project source.',
                    ],
                    [
                        'title' => 'GitHub release',
                        'url' => 'https://github.com/bitcoin/bitcoin/releases/tag/example',
                        'published_at' => now()->toDateString(),
                        'publisher' => 'GitHub',
                        'type' => 'technical',
                        'credibility_note' => 'Technical release artifact.',
                    ],
                ],
                'credibility_notes' => ['Grounded by Google Search redirect citations.'],
                'open_questions' => [],
            ],
        ],
    ]);

    expect($topics)->toHaveCount(1)
        ->and($topics->first()->constraints['news_research']['sources'])->toHaveCount(2);
});

test('console schedule includes the weekly news publishing slot', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));

    expect($schedule)
        ->toContain("Schedule::command('app:ideate-news-topics --count=1')")
        ->toContain("->weeklyOn(3, '08:00')")
        ->toContain("->weeklyOn(3, '08:10')");
});

test('generation command queues due topics', function () {
    Queue::fake();

    $topic = ContentTopic::factory()->due()->create();

    $this->artisan('app:generate-due-magazine-posts')->assertSuccessful();

    Queue::assertPushed(GeneratePostFromTopic::class, fn (GeneratePostFromTopic $job): bool => $job->topic->is($topic));
});

test('queue logs are written to a dedicated daily log file', function () {
    expect(config('logging.channels.queue.driver'))->toBe('daily')
        ->and(config('logging.channels.queue.path'))->toBe(storage_path('logs/queue.log'));
});

test('generation job failure writes useful queue log context', function () {
    $topic = ContentTopic::factory()->due()->create([
        'title' => 'Queue diagnostics topic',
    ]);
    $logger = Mockery::mock(LoggerInterface::class);

    Log::shouldReceive('channel')
        ->once()
        ->with('queue')
        ->andReturn($logger);

    $logger->shouldReceive('error')
        ->once()
        ->with('Magazine post generation job failed.', Mockery::on(
            fn (array $context): bool => $context['content_topic_id'] === $topic->id
                && $context['content_topic_title'] === 'Queue diagnostics topic'
                && $context['max_tries'] === 3
                && $context['timeout'] === 1200
                && $context['exception_class'] === RuntimeException::class
                && $context['exception_message'] === 'Queue worker stopped unexpectedly'
                && array_key_exists('memory_peak_mb', $context)
        ));

    (new GeneratePostFromTopic($topic))->failed(new RuntimeException('Queue worker stopped unexpectedly'));
});

test('generation job discards retries when the topic was deleted', function () {
    $attributes = (new ReflectionClass(GeneratePostFromTopic::class))
        ->getAttributes(DeleteWhenMissingModels::class);

    expect($attributes)->not->toBeEmpty();
});

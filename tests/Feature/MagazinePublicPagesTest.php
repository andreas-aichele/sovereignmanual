<?php

use App\Models\ContentTopic;
use App\Models\Post;
use App\Models\PostBlock;
use App\Models\PostTranslation;

test('published posts appear on the magazine index', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'self-custody',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
        'topic' => 'Bitcoin self custody basics',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin self custody basics',
        'slug' => 'bitcoin-self-custody-basics',
    ]);

    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertSee('Bitcoin self custody basics')
        ->assertSee('Self custody')
        ->assertSee('fallback.jpg');
});

test('newest published post leads the magazine index', function () {
    $olderPost = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
    ]);
    $newerPost = Post::factory()->published()->create([
        'published_at' => now()->subHour(),
    ]);

    PostTranslation::factory()->create([
        'post_id' => $olderPost->id,
        'locale' => 'en',
        'title' => 'Older article',
        'slug' => 'older-article',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $newerPost->id,
        'locale' => 'en',
        'title' => 'Newest article',
        'slug' => 'newest-article',
    ]);

    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertSeeInOrder(['Newest article', 'Older article']);
});

test('unpublished posts are hidden from the public magazine', function () {
    $post = Post::factory()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Hidden draft',
        'slug' => 'hidden-draft',
    ]);

    $this->get(route('magazine.show', 'hidden-draft'))->assertNotFound();
});

test('localized german posts render through the german route', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'bitcoin',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Selbstverwahrung',
        'slug' => 'bitcoin-selbstverwahrung',
        'markdown' => '# Bitcoin Selbstverwahrung',
    ]);

    $this->get(route('magazine.de.show', 'bitcoin-selbstverwahrung'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertViewHas('locale', 'de')
        ->assertSee('Bitcoin Selbstverwahrung')
        ->assertSee('fallback.jpg')
        ->assertSee('bitcoin')
        ->assertSee('Magazin')
        ->assertSee('Artikeldetails');
});

test('german category labels use correct umlauts', function () {
    $topic = ContentTopic::factory()->create([
        'category' => 'financial-independence',
    ]);

    $post = Post::factory()->published()->create([
        'content_topic_id' => $topic->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Finanzielle Unabhängigkeit',
        'slug' => 'finanzielle-unabhaengigkeit',
    ]);

    $this->get(route('magazine.de.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertSee('Finanzielle Unabhängigkeit');
});

test('markdown is rendered to sanitized html for articles', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Markdown Rendering',
        'slug' => 'markdown-rendering',
        'markdown' => "# Markdown Rendering\n\nA **strong** point.\n\n- first\n- second\n\n<script>alert('x')</script>",
    ]);

    $this->get(route('magazine.show', 'markdown-rendering'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<h1>Markdown Rendering</h1>', false)
        ->assertSee('<strong>strong</strong>', false)
        ->assertSee('<li>first</li>', false)
        ->assertDontSee('<script>', false);
});

test('arrow based diagram code blocks render as article diagrams', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Diagram Rendering',
        'slug' => 'diagram-rendering',
        'markdown' => "```\n[Traditional System]  --> [Intermediary / Bank]   --> [Your Money (Permissive)]\n[Bitcoin System]      --> [Your Private Keys]     --> [Your Money (Absolute)]\n```",
    ]);

    $this->get(route('magazine.show', 'diagram-rendering'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<pre class="mermaid">', false)
        ->assertSee('flowchart LR')
        ->assertSee('node_0_0[&quot;Traditional System&quot;] --&gt; node_0_1[&quot;Intermediary / Bank&quot;]', false)
        ->assertSee('node_1_1[&quot;Your Private Keys&quot;] --&gt; node_1_2[&quot;Your Money (Absolute)&quot;]', false)
        ->assertDontSee('<pre><code>[Traditional System]', false);
});

test('native mermaid code blocks render as mermaid diagrams', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Native Mermaid',
        'slug' => 'native-mermaid',
        'markdown' => "```mermaid\nflowchart TB\n    A[Bitcoin] --> B[Self custody]\n```",
    ]);

    $this->get(route('magazine.show', 'native-mermaid'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<pre class="mermaid">', false)
        ->assertSee('flowchart TB')
        ->assertSee('A[Bitcoin] --&gt; B[Self custody]', false)
        ->assertDontSee('language-mermaid');
});

test('regular code blocks remain code when they are not diagrams', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Code Rendering',
        'slug' => 'code-rendering',
        'markdown' => "```php\n\$wallet = 'cold storage';\nreturn \$wallet;\n```",
    ]);

    $this->get(route('magazine.show', 'code-rendering'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<pre><code class="language-php">', false)
        ->assertDontSee('class="mermaid"', false);
});

test('article pages render seo meta tags with limits and keywords', function () {
    $post = Post::factory()->published()->create([
        'seo' => ['keywords' => ['bitcoin', 'self custody']],
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Custody',
        'slug' => 'bitcoin-custody',
        'meta_title' => str_repeat('Long title ', 10),
        'meta_description' => str_repeat('Long description ', 20),
        'seo' => ['keywords' => ['bitcoin', 'wallet security']],
    ]);

    $response = $this->get(route('magazine.show', 'bitcoin-custody'))
        ->assertSuccessful()
        ->assertSee('name="keywords" content="bitcoin, wallet security"', false);

    expect(mb_strlen($response->viewData('meta')['title']))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($response->viewData('meta')['description']))->toBeLessThanOrEqual(160);
});

test('sitemap lists public magazine urls for all translations', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Basics',
        'slug' => 'bitcoin-basics',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Grundlagen',
        'slug' => 'bitcoin-grundlagen',
    ]);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee(route('magazine.index'), false)
        ->assertSee(route('magazine.show', 'bitcoin-basics'), false)
        ->assertSee(route('magazine.de.show', 'bitcoin-grundlagen'), false);
});

test('article headings render table of contents anchor links', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Linked Contents',
        'slug' => 'linked-contents',
        'markdown' => "## Risk Model\n\nText.\n\n### Cold Storage\n\nText.\n\n## Risk Model\n\nDuplicate heading.",
    ]);

    $this->get(route('magazine.show', 'linked-contents'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('Contents')
        ->assertSee('<h2 id="risk-model">Risk Model</h2>', false)
        ->assertSee('<h3 id="cold-storage">Cold Storage</h3>', false)
        ->assertSee('<h2 id="risk-model-2">Risk Model</h2>', false)
        ->assertSee('href="#risk-model"', false)
        ->assertSee('href="#cold-storage"', false)
        ->assertSee('href="#risk-model-2"', false);
});

test('structured post blocks provide table of contents anchors before markdown fallback parsing', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Structured Contents',
        'slug' => 'structured-contents',
        'markdown' => '## Legacy Heading',
    ]);

    PostBlock::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'type' => 'section',
        'sort_order' => 0,
        'heading' => 'Custody Basics',
        'anchor' => 'custody-basics',
        'markdown' => 'A structured section.',
    ]);

    $this->get(route('magazine.show', 'structured-contents'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<h2 id="custody-basics">Custody Basics</h2>', false)
        ->assertSee('href="#custody-basics"', false)
        ->assertDontSee('Legacy Heading');
});

test('structured flow diagram blocks render as article diagrams', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Structured Diagram',
        'slug' => 'structured-diagram',
        'markdown' => 'Legacy text.',
    ]);

    PostBlock::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'type' => 'flow_diagram',
        'sort_order' => 0,
        'data' => [
            'title' => 'Decision path',
            'steps' => ['Goal', 'Risk', 'Test'],
        ],
    ]);

    $this->get(route('magazine.show', 'structured-diagram'))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<pre class="mermaid">', false)
        ->assertSee('%% Decision path')
        ->assertSee('node_0_0[&quot;Goal&quot;] --&gt; node_0_1[&quot;Risk&quot;]', false)
        ->assertSee('node_0_1[&quot;Risk&quot;] --&gt; node_0_2[&quot;Test&quot;]', false);
});

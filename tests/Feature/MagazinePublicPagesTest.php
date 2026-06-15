<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\PostBlock;
use App\Models\PostTranslation;

test('published posts appear on the magazine index', function () {
    $category = Category::query()->firstOrCreate(
        ['slug' => 'self-custody'],
        ['name' => [
            'en' => 'Self custody',
            'de' => 'Selbstverwahrung',
        ]]
    );

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
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
        ->assertSee('Self Custody')
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'hidden-draft']))->assertNotFound();
});

test('localized german posts render through the german route', function () {
    $category = Category::query()->firstOrCreate(
        ['slug' => 'self-custody'],
        ['name' => ['en' => 'Self Custody', 'de' => 'Selbstverwahrung']]
    );

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Selbstverwahrung',
        'slug' => 'bitcoin-selbstverwahrung',
        'markdown' => '# Bitcoin Selbstverwahrung',
    ]);

    $this->get(route('magazine.de.show', ['category' => 'self-custody', 'slug' => 'bitcoin-selbstverwahrung']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertViewHas('locale', 'de')
        ->assertCookie('locale', 'de')
        ->assertSee('<html lang="de"', false)
        ->assertSee('Bitcoin Selbstverwahrung')
        ->assertSee('fallback.jpg')
        ->assertSee('bitcoin')
        ->assertSee('Magazin')
        ->assertSee('Artikeldetails');
});

test('article urls are scoped by their real category', function () {
    $category = Category::query()->firstOrCreate(
        ['slug' => 'self-custody'],
        ['name' => [
            'en' => 'Self custody',
            'de' => 'Selbstverwahrung',
        ]]
    );

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Wallet Backups',
        'slug' => 'wallet-backups',
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'wallet-backups']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show');

    $this->get(route('magazine.show', ['category' => 'privacy-security', 'slug' => 'wallet-backups']))
        ->assertNotFound();
});

test('legacy magazine article urls redirect to category urls', function () {
    $category = Category::query()->firstOrCreate(
        ['slug' => 'self-custody'],
        ['name' => [
            'en' => 'Self custody',
            'de' => 'Selbstverwahrung',
        ]]
    );

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Wallet Backups',
        'slug' => 'wallet-backups',
    ]);

    $this->get(route('magazine.legacy.show', 'wallet-backups'))
        ->assertMovedPermanently()
        ->assertRedirect(route('magazine.show', ['category' => 'self-custody', 'slug' => 'wallet-backups']));
});

test('german category labels use correct umlauts', function () {
    $category = Category::query()->firstOrCreate(
        ['slug' => 'financial-sovereignty'],
        ['name' => [
            'en' => 'Financial Sovereignty',
            'de' => 'Finanzielle Souveränität',
        ]]
    );

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Finanzielle Unabhängigkeit',
        'slug' => 'finanzielle-unabhaengigkeit',
    ]);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertSee('<html lang="de"', false)
        ->assertSee('Finanzielle Unabhängigkeit');
});

test('stored locale controls the public magazine start page language', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Grundlagen',
        'slug' => 'bitcoin-grundlagen',
    ]);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertCookie('locale', 'de')
        ->assertSee('<html lang="de"', false)
        ->assertSee('Bitcoin Grundlagen')
        ->assertSee('Artikel lesen');
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'markdown-rendering']))
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'diagram-rendering']))
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'native-mermaid']))
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'code-rendering']))
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

    $response = $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-custody']))
        ->assertSuccessful()
        ->assertSee('name="keywords" content="bitcoin, wallet security"', false);

    expect(mb_strlen($response->viewData('meta')['title']))->toBeLessThanOrEqual(60)
        ->and(mb_strlen($response->viewData('meta')['description']))->toBeLessThanOrEqual(160);
});

test('sitemap index links to paginated sitemap files', function () {
    $post = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
        'updated_at' => now()->subDay(),
    ]);

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
        ->assertSee('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee('<loc>'.route('sitemap.page', 1).'</loc>', false)
        ->assertSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false);
});

test('paginated sitemap lists public magazine urls for all translations', function () {
    $post = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
        'updated_at' => now()->subDay(),
    ]);

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

    $this->get(route('sitemap.page', 1))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee(route('magazine.index'), false)
        ->assertSee('<loc>'.route('magazine.index').'</loc>', false)
        ->assertSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false)
        ->assertSee('<changefreq>daily</changefreq>', false)
        ->assertSee('<priority>1.0</priority>', false)
        ->assertDontSee('<loc>'.route('magazine.de.index').'</loc>', false)
        ->assertSee(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-basics']), false)
        ->assertSee(route('magazine.de.show', ['category' => 'self-custody', 'slug' => 'bitcoin-grundlagen']), false)
        ->assertSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false)
        ->assertSee('<changefreq>monthly</changefreq>', false)
        ->assertSee('<priority>0.8</priority>', false);
});

test('sitemap homepage lastmod uses latest published article', function () {
    Post::factory()->published()->create([
        'published_at' => now()->subDays(10),
        'updated_at' => now()->subDay(),
    ]);

    Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $this->get(route('sitemap.page', 1))
        ->assertSuccessful()
        ->assertSee('<loc>'.route('magazine.index').'</loc>', false)
        ->assertSee('<lastmod>'.now()->subDays(2)->toDateString().'</lastmod>', false)
        ->assertSee('<changefreq>daily</changefreq>', false)
        ->assertSee('<priority>1.0</priority>', false)
        ->assertDontSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false);
});

test('sitemap pages are split by configured page size', function () {
    config(['app.sitemap_per_page' => 2]);

    $post = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
        'updated_at' => now()->subDay(),
    ]);

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
        ->assertSee('<loc>'.route('sitemap.page', 1).'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.page', 2).'</loc>', false)
        ->assertDontSee('<loc>'.route('sitemap.page', 3).'</loc>', false);

    $this->get(route('sitemap.page', 1))
        ->assertSuccessful()
        ->assertSee(route('magazine.index'), false)
        ->assertSee(route('magazine.de.show', ['category' => 'self-custody', 'slug' => 'bitcoin-grundlagen']), false)
        ->assertDontSee(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-basics']), false);

    $this->get(route('sitemap.page', 2))
        ->assertSuccessful()
        ->assertDontSee('<loc>'.route('magazine.index').'</loc>', false)
        ->assertSee(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-basics']), false);

    $this->get('/sitemap-3.xml')->assertNotFound();
});

test('article h2 headings render table of contents anchor links', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Linked Contents',
        'slug' => 'linked-contents',
        'markdown' => "## Risk Model\n\nText.\n\n### Cold Storage\n\nText.\n\n#### Key Rotation\n\nText.\n\n## Risk Model\n\nDuplicate heading.",
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'linked-contents']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('Contents')
        ->assertSee('<h2 id="risk-model">Risk Model</h2>', false)
        ->assertSee('<h3>Cold Storage</h3>', false)
        ->assertSee('<h4>Key Rotation</h4>', false)
        ->assertSee('<h2 id="risk-model-2">Risk Model</h2>', false)
        ->assertSee('data-toc-link', false)
        ->assertSee('aria-[current=location]:font-semibold', false)
        ->assertSee('href="#risk-model"', false)
        ->assertSee('href="#risk-model-2"', false)
        ->assertDontSee('href="#cold-storage"', false)
        ->assertDontSee('href="#key-rotation"', false)
        ->assertDontSee('id="cold-storage"', false)
        ->assertDontSee('id="key-rotation"', false);
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'structured-contents']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<h2 id="custody-basics">Custody Basics</h2>', false)
        ->assertSee('href="#custody-basics"', false)
        ->assertDontSee('Legacy Heading');
});

test('structured post block markdown headings are included in the table of contents', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Complete Structured Contents',
        'slug' => 'complete-structured-contents',
        'markdown' => '## Legacy Heading',
    ]);

    PostBlock::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'type' => 'section',
        'sort_order' => 0,
        'heading' => 'Custody Basics',
        'anchor' => 'custody-basics',
        'markdown' => "Intro.\n\n## Wallet Setup\n\nText.\n\n## Recovery Plan\n\nText.",
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'complete-structured-contents']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<h2 id="custody-basics">Custody Basics</h2>', false)
        ->assertSee('<h2 id="wallet-setup">Wallet Setup</h2>', false)
        ->assertSee('<h2 id="recovery-plan">Recovery Plan</h2>', false)
        ->assertSee('href="#custody-basics"', false)
        ->assertSee('href="#wallet-setup"', false)
        ->assertSee('href="#recovery-plan"', false);
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

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'structured-diagram']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<pre class="mermaid">', false)
        ->assertSee('%% Decision path')
        ->assertSee('node_0_0[&quot;Goal&quot;] --&gt; node_0_1[&quot;Risk&quot;]', false)
        ->assertSee('node_0_1[&quot;Risk&quot;] --&gt; node_0_2[&quot;Test&quot;]', false);
});

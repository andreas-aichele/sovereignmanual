<?php

use App\Enums\Language;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostAsset;
use App\Models\PostBlock;
use App\Models\PostTranslation;
use App\Support\ResponsiveImage;
use Illuminate\Support\Facades\Storage;

function selfCustodyCategory(): Category
{
    selbstverwahrungCategory();

    return Category::query()->updateOrCreate(
        ['key' => 'self-custody', 'lang' => Language::English],
        [
            'slug' => 'self-custody',
            'name' => 'Self Custody',
            'description' => 'Practical guidance for holding keys, building recovery plans, and reducing custody risk.',
        ],
    );
}

function selbstverwahrungCategory(): Category
{
    return Category::query()->updateOrCreate(
        ['key' => 'self-custody', 'lang' => Language::German],
        [
            'slug' => 'selbstverwahrung',
            'name' => 'Selbstverwahrung',
            'description' => 'Praktische Orientierung für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken.',
        ],
    );
}

test('published posts appear on the magazine index', function () {
    $category = selfCustodyCategory();

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
        ->assertSee('<picture class="block h-full w-full">', false)
        ->assertSee('loading="eager"', false)
        ->assertSee('fetchpriority="high"', false)
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
    $category = selfCustodyCategory();

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

    $this->get(route('magazine.localized.show', ['locale' => 'de', 'category' => 'selbstverwahrung', 'slug' => 'bitcoin-selbstverwahrung']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertViewHas('locale', 'de')
        ->assertCookie('locale', 'de')
        ->assertSee('lang="de"', false)
        ->assertSee('Bitcoin Selbstverwahrung')
        ->assertSee('fallback.jpg')
        ->assertSee('bitcoin')
        ->assertSee('Magazin')
        ->assertSee('Artikeldetails');
});

test('article urls are scoped by their real category', function () {
    $category = selfCustodyCategory();

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

test('article pages link to their localized category page', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Wallet Backups',
        'slug' => 'wallet-backups',
    ]);

    $this->get(route('magazine.localized.show', ['locale' => 'de', 'category' => 'selbstverwahrung', 'slug' => 'wallet-backups']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('/de/selbstverwahrung"', false);
});

test('category page renders heading description and paginated article listing', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Selbstverwahrung',
        'slug' => 'bitcoin-selbstverwahrung',
    ]);

    $this->get(route('magazine.localized.category', ['locale' => 'de', 'category' => 'selbstverwahrung']))
        ->assertSuccessful()
        ->assertViewIs('magazine.category')
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee('href="'.route('magazine.index').'"', false)
        ->assertSee('Magazin')
        ->assertSee('aria-current="page"', false)
        ->assertSee('Selbstverwahrung')
        ->assertSee('Praktische Orientierung')
        ->assertSee('Bitcoin Selbstverwahrung')
        ->assertSee('<picture class="block h-full w-full">', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('fallback.jpg');
});

test('german category page also resolves the stable category key as a legacy alias', function () {
    selfCustodyCategory();

    $this->get(route('magazine.localized.category', ['locale' => 'de', 'category' => 'self-custody']))
        ->assertSuccessful()
        ->assertViewIs('magazine.category')
        ->assertSee('Selbstverwahrung');
});

test('self custody route supports legacy published posts without a category', function () {
    $post = Post::factory()->published()->create([
        'category_id' => null,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Legacy Self Custody',
        'slug' => 'legacy-self-custody',
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'legacy-self-custody']))
        ->assertSuccessful()
        ->assertSee('Legacy Self Custody')
        ->assertSee('Self Custody');

    $this->get(route('magazine.show', ['category' => 'privacy-security', 'slug' => 'legacy-self-custody']))
        ->assertNotFound();
});

test('german category labels use correct umlauts', function () {
    $category = Category::factory()->create([
        'key' => 'financial-sovereignty',
        'lang' => Language::English,
        'slug' => 'financial-sovereignty',
        'name' => 'Financial Sovereignty',
        'description' => 'Financial sovereignty articles.',
    ]);
    Category::factory()->create([
        'key' => 'financial-sovereignty',
        'lang' => Language::German,
        'slug' => 'finanzielle-souveraenitaet',
        'name' => 'Finanzielle Souveränität',
        'description' => 'Artikel über finanzielle Souveränität.',
    ]);

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Finanzielle Unabhängigkeit',
        'slug' => 'finanzielle-unabhaengigkeit',
    ]);

    $this->get(route('magazine.localized.index', ['locale' => 'de']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertSee('lang="de"', false)
        ->assertSee('Finanzielle Unabhängigkeit');
});

test('stored locale does not change the x default magazine start page language', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Basics',
        'slug' => 'bitcoin-basics',
    ]);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('lang="en"', false)
        ->assertSee('Bitcoin Basics')
        ->assertSee('Read article');
});

test('start page language switcher links directly to localized start pages', function () {
    $response = $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewHas('locale', 'en');

    expect($response->viewData('languageOptions'))->toContain([
        'locale' => 'de',
        'label' => 'Deutsch',
        'url' => route('magazine.localized.index', ['locale' => 'de']),
        'current' => false,
    ]);

    $this->get(route('magazine.localized.index', ['locale' => 'de']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertCookie('locale', 'de');

    $germanResponse = $this->withCookie('locale', 'de')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewHas('locale', 'en');

    expect($germanResponse->viewData('languageOptions'))->toContain([
        'locale' => 'de',
        'label' => 'Deutsch',
        'url' => route('magazine.localized.index', ['locale' => 'de']),
        'current' => false,
    ]);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.localized.index', ['locale' => 'en']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en');

    $this->withCookie('locale', 'en')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewHas('locale', 'en');
});

test('article language switcher only links to real translated article urls', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'English only article',
        'slug' => 'english-only-article',
    ]);

    $response = $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'english-only-article']))
        ->assertSuccessful();

    expect($response->viewData('languageOptions'))
        ->toHaveCount(1)
        ->sequence(fn ($option) => $option
            ->locale->toBe('en')
            ->url->toBe(route('magazine.localized.show', [
                'locale' => 'en',
                'category' => 'self-custody',
                'slug' => 'english-only-article',
            ])));
});

test('article language switcher uses localized category and slug for translated articles', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Seed Backup',
        'slug' => 'seed-backup',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Seed Backup Deutsch',
        'slug' => 'seed-backup-deutsch',
    ]);

    $germanUrl = route('magazine.localized.show', [
        'locale' => 'de',
        'category' => 'selbstverwahrung',
        'slug' => 'seed-backup-deutsch',
    ]);
    $englishUrl = route('magazine.localized.show', [
        'locale' => 'en',
        'category' => 'self-custody',
        'slug' => 'seed-backup',
    ]);

    $response = $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'seed-backup']))
        ->assertSuccessful();

    expect($response->viewData('languageOptions'))->toContain([
        'locale' => 'de',
        'label' => 'Deutsch',
        'url' => $germanUrl,
        'current' => false,
    ]);

    $this->get($germanUrl)->assertSuccessful();

    $germanResponse = $this->withCookie('locale', 'de')
        ->get($germanUrl)
        ->assertSuccessful()
        ->assertViewHas('locale', 'de');

    expect($germanResponse->viewData('languageOptions'))->toContain([
        'locale' => 'en',
        'label' => 'English',
        'url' => $englishUrl,
        'current' => false,
    ]);

    expect($germanResponse->viewData('meta')['alternate'])->toBe($englishUrl);

    $this->withCookie('locale', 'de')
        ->get($englishUrl)
        ->assertSuccessful()
        ->assertViewHas('locale', 'en')
        ->assertSee('Seed Backup');
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

test('article images render responsive picture markup', function () {
    $post = Post::factory()->published()->create();
    $responsiveImage = [
        'src' => '/storage/post-assets/header.png',
        'width' => 1600,
        'height' => 900,
        'sources' => [
            'avif' => [
                ['url' => '/storage/post-assets/responsive/header-768.avif', 'width' => 768, 'height' => 432],
                ['url' => '/storage/post-assets/responsive/header-1600.avif', 'width' => 1600, 'height' => 900],
            ],
            'webp' => [
                ['url' => '/storage/post-assets/responsive/header-768.webp', 'width' => 768, 'height' => 432],
            ],
            'jpg' => [
                ['url' => '/storage/post-assets/responsive/header-768.jpg', 'width' => 768, 'height' => 432],
            ],
        ],
    ];
    $blockResponsiveImage = [
        'src' => '/storage/post-assets/block.png',
        'width' => 1200,
        'height' => 800,
        'sources' => [
            'webp' => [
                ['url' => '/storage/post-assets/responsive/block-768.webp', 'width' => 768, 'height' => 512],
            ],
        ],
    ];

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Responsive Images',
        'slug' => 'responsive-images',
    ]);

    PostAsset::factory()->create([
        'post_id' => $post->id,
        'url' => '/storage/post-assets/header.png',
        'alt_text' => 'Header image alt text',
        'metadata' => [
            'role' => 'header',
            'responsive_image' => $responsiveImage,
        ],
    ]);

    $blockAsset = PostAsset::factory()->create([
        'post_id' => $post->id,
        'url' => '/storage/post-assets/block.png',
        'alt_text' => 'Block image alt text',
        'metadata' => [
            'responsive_image' => $blockResponsiveImage,
        ],
    ]);

    PostBlock::factory()->create([
        'post_id' => $post->id,
        'post_asset_id' => $blockAsset->id,
        'locale' => 'en',
        'markdown' => 'Block body.',
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'responsive-images']))
        ->assertSuccessful()
        ->assertSee('<picture class="block h-full w-full">', false)
        ->assertSee('type="image/avif"', false)
        ->assertSee('/storage/post-assets/responsive/header-768.avif 768w, /storage/post-assets/responsive/header-1600.avif 1600w', false)
        ->assertSee('type="image/jpeg"', false)
        ->assertSee('sizes="(min-width: 72rem) 72rem, 100vw"', false)
        ->assertSee('src="/storage/post-assets/header.png"', false)
        ->assertSee('alt="Header image alt text"', false)
        ->assertSee('width="1600"', false)
        ->assertSee('height="900"', false)
        ->assertSee('loading="eager"', false)
        ->assertSee('fetchpriority="high"', false)
        ->assertSee('src="/storage/post-assets/block.png"', false)
        ->assertSee('alt="Block image alt text"', false)
        ->assertSee('loading="lazy"', false);
});

test('responsive image command converts stored assets without responsive metadata', function () {
    app()->instance(ResponsiveImage::class, new class extends ResponsiveImage
    {
        /**
         * @return array<string, mixed>|null
         */
        public function generate(string $disk, string $path, array $widths = [480, 768, 1024, 1360, 1800]): ?array
        {
            return [
                'src' => "/storage/{$path}",
                'width' => 1200,
                'height' => 675,
                'sources' => [
                    'webp' => [
                        ['url' => '/storage/post-assets/responsive/existing-768.webp', 'width' => 768, 'height' => 432],
                    ],
                ],
            ];
        }
    });

    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Existing Image',
        'slug' => 'existing-image',
    ]);

    $asset = PostAsset::factory()->create([
        'post_id' => $post->id,
        'path' => 'post-assets/existing.jpg',
        'url' => 'https://sovereignmanual.com/storage/post-assets/existing.jpg',
        'metadata' => [
            'role' => 'header',
            'responsive_image' => [
                'src' => 'https://sovereignmanual.com/storage/post-assets/existing.jpg',
                'width' => null,
                'height' => null,
                'sources' => [],
            ],
        ],
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'existing-image']))
        ->assertSuccessful()
        ->assertSee('post-assets/existing.jpg')
        ->assertDontSee('https://sovereignmanual.com/storage/post-assets/existing.jpg', false);

    expect($asset->refresh()->metadata['responsive_image']['sources'])->toBe([]);

    $this->artisan('app:generate-responsive-post-images --force')
        ->assertSuccessful();

    expect($asset->refresh()->metadata['responsive_image']['sources']['webp'][0]['width'])->toBe(768);
});

test('responsive image command does not store empty metadata when generation fails', function () {
    app()->instance(ResponsiveImage::class, new class extends ResponsiveImage
    {
        public function generate(string $disk, string $path, array $widths = [480, 768, 1024, 1360, 1800]): ?array
        {
            return null;
        }
    });

    $asset = PostAsset::factory()->create([
        'path' => 'post-assets/missing.jpg',
        'metadata' => [
            'role' => 'header',
        ],
    ]);

    $this->artisan('app:generate-responsive-post-images')
        ->assertFailed();

    expect($asset->refresh()->metadata)->not->toHaveKey('responsive_image');
});

test('responsive image service generates variants from stored images', function () {
    Storage::fake('public');
    Storage::disk('public')->put(
        'post-assets/test.jpg',
        base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAHsP//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCcf/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8BP//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8BP//Z')
    );

    $responsiveImage = app(ResponsiveImage::class)->generate('public', 'post-assets/test.jpg', [1]);

    expect($responsiveImage['width'])->toBe(1)
        ->and($responsiveImage['height'])->toBe(1)
        ->and($responsiveImage['sources'])->not->toBeEmpty();
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
        ->assertSuccessful();

    expect(mb_strlen($response->viewData('meta')['title']))->toBeLessThanOrEqual(60)
        ->and($response->viewData('meta')['keywords'])->toBe(['bitcoin', 'wallet security'])
        ->and(mb_strlen($response->viewData('meta')['description']))->toBeLessThanOrEqual(160);
});

test('public index renders canonical alternate social and structured data tags', function () {
    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<meta name="theme-color" content="#1a103d">', false)
        ->assertSee('<link href="'.route('magazine.index').'" rel="canonical">', false)
        ->assertSee('href="'.route('magazine.localized.index', ['locale' => 'en']).'"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('href="'.route('magazine.localized.index', ['locale' => 'de']).'"', false)
        ->assertSee('hreflang="de"', false)
        ->assertSee('href="'.route('magazine.index').'"', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:locale" content="en_US">', false)
        ->assertSee('property="og:locale:alternate"', false)
        ->assertSee('content="de_DE"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('content="Sovereign Manual Magazine"', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee('content="summary"', false)
        ->assertSee('<script type="application/ld+json">', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('Sovereign Manual Magazine');
});

test('localized start pages are indexable with their own canonical urls', function () {
    $this->get(route('magazine.localized.index', ['locale' => 'en']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertSee('<link href="'.route('magazine.localized.index', ['locale' => 'en']).'" rel="canonical">', false)
        ->assertDontSee('<link href="'.route('magazine.index').'" rel="canonical">', false)
        ->assertSee('href="'.route('magazine.index').'"', false)
        ->assertSee('hreflang="x-default"', false);

    $this->get(route('magazine.localized.index', ['locale' => 'de']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertSee('<link href="'.route('magazine.localized.index', ['locale' => 'de']).'" rel="canonical">', false)
        ->assertDontSee('<link href="'.route('magazine.index').'" rel="canonical">', false)
        ->assertSee('<meta property="og:locale" content="de_DE">', false)
        ->assertSee('content="en_US"', false)
        ->assertSee('href="'.route('magazine.index').'"', false)
        ->assertSee('hreflang="x-default"', false);
});

test('category pages render canonical alternate social and structured data tags', function () {
    selfCustodyCategory();

    $this->get(route('magazine.category', ['category' => 'self-custody']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<meta name="theme-color" content="#1a103d">', false)
        ->assertSee('<link href="'.route('magazine.category', ['category' => 'self-custody']).'" rel="canonical">', false)
        ->assertSee('href="'.route('magazine.localized.category', ['locale' => 'en', 'category' => 'self-custody']).'"', false)
        ->assertSee('href="'.route('magazine.localized.category', ['locale' => 'de', 'category' => 'selbstverwahrung']).'"', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:locale" content="en_US">', false)
        ->assertSee('"@type":"CollectionPage"', false);
});

test('article pages render canonical hreflang social and article structured data tags', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
        'published_at' => now()->subDays(3),
        'updated_at' => now()->subDay(),
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Custody SEO',
        'slug' => 'bitcoin-custody-seo',
        'meta_title' => 'Bitcoin Custody SEO',
        'meta_description' => 'A practical article about Bitcoin self custody metadata.',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Verwahrung SEO',
        'slug' => 'bitcoin-verwahrung-seo',
        'meta_description' => 'Ein praktischer Artikel über Bitcoin Selbstverwahrung.',
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-custody-seo']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<meta name="theme-color" content="#1a103d">', false)
        ->assertSee('<meta name="author" content="Sovereign Manual">', false)
        ->assertSee('<link href="'.route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-custody-seo']).'" rel="canonical">', false)
        ->assertSee('href="'.route('magazine.localized.show', ['locale' => 'en', 'category' => 'self-custody', 'slug' => 'bitcoin-custody-seo']).'"', false)
        ->assertSee('href="'.route('magazine.localized.show', ['locale' => 'de', 'category' => 'selbstverwahrung', 'slug' => 'bitcoin-verwahrung-seo']).'"', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:locale" content="en_US">', false)
        ->assertSee('property="og:locale:alternate"', false)
        ->assertSee('content="de_DE"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('content="Bitcoin Custody SEO"', false)
        ->assertSee('<meta property="og:image"', false)
        ->assertSee('property="article:published_time"', false)
        ->assertSee('content="'.$post->published_at->toAtomString().'"', false)
        ->assertSee('property="article:modified_time"', false)
        ->assertSee('content="'.$post->updated_at->toAtomString().'"', false)
        ->assertSee('<meta property="article:section" content="Self Custody">', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee('content="summary_large_image"', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"headline":"Bitcoin Custody SEO"', false)
        ->assertSee('"datePublished":"'.$post->published_at->toAtomString().'"', false);
});

test('article seo urls honor forwarded https scheme', function () {
    $category = selfCustodyCategory();

    $post = Post::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Bitcoin Custody SEO',
        'slug' => 'bitcoin-custody-seo',
        'meta_title' => 'Bitcoin Custody SEO',
        'meta_description' => 'A practical article about Bitcoin self custody metadata.',
    ]);

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'de',
        'title' => 'Bitcoin Verwahrung SEO',
        'slug' => 'bitcoin-verwahrung-seo',
        'meta_description' => 'Ein praktischer Artikel uber Bitcoin Selbstverwahrung.',
    ]);

    $this->withHeader('X-Forwarded-Proto', 'https')
        ->withHeader('X-Forwarded-Host', 'sovereignmanual.com')
        ->get('http://sovereignmanual.com/self-custody/bitcoin-custody-seo')
        ->assertSuccessful()
        ->assertSee('<link href="https://sovereignmanual.com/self-custody/bitcoin-custody-seo" rel="canonical">', false)
        ->assertSee('href="https://sovereignmanual.com/en/self-custody/bitcoin-custody-seo"', false)
        ->assertSee('href="https://sovereignmanual.com/de/selbstverwahrung/bitcoin-verwahrung-seo"', false)
        ->assertDontSee('href="http://sovereignmanual.com', false);
});

test('sitemap index links to content type sitemap files', function () {
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
        ->assertSee('<loc>'.route('sitemap.posts').'</loc>', false)
        ->assertSee('<loc>'.route('sitemap.categories').'</loc>', false)
        ->assertSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false);
});

test('post sitemap lists public magazine urls for all translations', function () {
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

    $this->get(route('sitemap.posts'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-basics']), false)
        ->assertSee(route('magazine.localized.show', ['locale' => 'de', 'category' => 'selbstverwahrung', 'slug' => 'bitcoin-grundlagen']), false)
        ->assertSee('<lastmod>'.now()->subDay()->toDateString().'</lastmod>', false)
        ->assertSee('<changefreq>monthly</changefreq>', false)
        ->assertSee('<priority>0.8</priority>', false)
        ->assertDontSee('<loc>'.route('magazine.category', ['category' => 'self-custody']).'</loc>', false);
});

test('category sitemap lists category urls', function () {
    selfCustodyCategory();

    $this->get(route('sitemap.categories'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee('<loc>'.route('magazine.category', ['category' => 'self-custody']).'</loc>', false)
        ->assertSee('<loc>'.route('magazine.localized.category', ['locale' => 'de', 'category' => 'selbstverwahrung']).'</loc>', false)
        ->assertSee('<changefreq>weekly</changefreq>', false)
        ->assertSee('<priority>0.9</priority>', false)
        ->assertDontSee(route('magazine.show', ['category' => 'self-custody', 'slug' => 'bitcoin-basics']), false);
});

test('numbered sitemap pages no longer exist', function () {
    $this->get('/sitemap-1.xml')
        ->assertNotFound();
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

test('non section structured blocks render markdown when available', function () {
    $post = Post::factory()->published()->create();

    PostTranslation::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'title' => 'Insight Block',
        'slug' => 'insight-block',
        'markdown' => 'Legacy text.',
    ]);

    PostBlock::factory()->create([
        'post_id' => $post->id,
        'locale' => 'en',
        'type' => 'insight',
        'sort_order' => 0,
        'heading' => 'Ignored heading',
        'markdown' => '**Key insight:** hold your own keys.',
    ]);

    $this->get(route('magazine.show', ['category' => 'self-custody', 'slug' => 'insight-block']))
        ->assertSuccessful()
        ->assertViewIs('magazine.show')
        ->assertSee('<strong>Key insight:</strong> hold your own keys.', false)
        ->assertDontSee('Legacy text.');
});

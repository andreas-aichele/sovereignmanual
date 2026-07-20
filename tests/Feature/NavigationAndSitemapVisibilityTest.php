<?php

use App\Enums\Language;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\CategorySeeder;

function bitcoinMoneyCategory(): Category
{
    return Category::query()
        ->where('key', 'self-custody')
        ->where('lang', Language::English)
        ->firstOrFail();
}

function publishPostsInCategory(Category $category, int $count): void
{
    $posts = Post::factory()
        ->count($count)
        ->published()
        ->create([
            'category_id' => $category->id,
            'published_at' => now()->subMinute(),
        ]);

    $posts->each(function (Post $post): void {
        PostTranslation::factory()->create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => "Pillar post {$post->id}",
            'slug' => "pillar-post-{$post->id}",
        ]);
        PostTranslation::factory()->create([
            'post_id' => $post->id,
            'locale' => 'de',
            'title' => "Pillar-Beitrag {$post->id}",
            'slug' => "pillar-beitrag-{$post->id}",
        ]);
    });
}

test('public navigation omits empty categories and only promotes established pillars', function () {
    $this->seed(CategorySeeder::class);
    $category = bitcoinMoneyCategory();

    $emptyNavigation = view('components.public-nav', ['locale' => 'en'])->render();

    expect($emptyNavigation)->not->toContain('Topics')
        ->and($emptyNavigation)->not->toContain('Categories')
        ->and($emptyNavigation)->not->toContain('Self Custody');

    publishPostsInCategory($category, 1);

    $categoryNavigation = view('components.public-nav', ['locale' => 'en'])->render();

    expect($categoryNavigation)->toContain('Categories')
        ->and($categoryNavigation)->toContain('Self Custody')
        ->and($categoryNavigation)->not->toContain('Privacy &amp; Security');

    publishPostsInCategory($category, 5);

    $pillarNavigation = view('components.public-nav', ['locale' => 'en'])->render();

    expect($pillarNavigation)->toContain('Topics')
        ->and($pillarNavigation)->toContain('Bitcoin &amp; Money')
        ->and($pillarNavigation)->not->toContain('Self Custody')
        ->and($pillarNavigation)->not->toContain('Digital Sovereignty');
});

test('sitemaps omit empty categories and only include pillar hubs after six published posts', function () {
    $this->seed(CategorySeeder::class);
    $category = bitcoinMoneyCategory();

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertDontSee('<loc>'.route('sitemap.categories').'</loc>', false)
        ->assertDontSee('<loc>'.route('sitemap.pillars').'</loc>', false);

    publishPostsInCategory($category, 5);

    $selfCustodyUrl = route('magazine.category', ['category' => 'self-custody']);
    $selfCustodyGermanUrl = route('magazine.localized.category', [
        'locale' => 'de',
        'category' => 'selbstverwahrung',
    ]);
    $emptyCategoryUrl = route('magazine.category', ['category' => 'privacy-security']);
    $bitcoinMoneyUrl = route('magazine.pillar.show', ['pillar' => 'bitcoin-money']);
    $bitcoinMoneyGermanUrl = route('magazine.localized.pillar.show', [
        'locale' => 'de',
        'pillar' => 'bitcoin-geld',
    ]);

    $this->get(route('sitemap.categories'))
        ->assertSuccessful()
        ->assertSee('<loc>'.$selfCustodyUrl.'</loc>', false)
        ->assertSee('<loc>'.$selfCustodyGermanUrl.'</loc>', false)
        ->assertDontSee('<loc>'.$emptyCategoryUrl.'</loc>', false);

    $this->get(route('sitemap.pillars'))
        ->assertSuccessful()
        ->assertDontSee('<loc>'.$bitcoinMoneyUrl.'</loc>', false);

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertDontSee('<loc>'.route('sitemap.pillars').'</loc>', false);

    publishPostsInCategory($category, 1);

    $this->get(route('sitemap.pillars'))
        ->assertSuccessful()
        ->assertSee('<loc>'.$bitcoinMoneyUrl.'</loc>', false)
        ->assertSee('<loc>'.$bitcoinMoneyGermanUrl.'</loc>', false);

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee('<loc>'.route('sitemap.pillars').'</loc>', false);
});

test('unlocalized pillar hubs keep the fallback locale despite a German locale cookie', function () {
    $this->seed(CategorySeeder::class);

    $this->withCookie('locale', 'de')
        ->get(route('magazine.pillar.show', ['pillar' => 'bitcoin-money']))
        ->assertSuccessful()
        ->assertViewIs('magazine.pillar')
        ->assertViewHas('locale', 'en')
        ->assertCookie('locale', 'en')
        ->assertSee('<link href="'.route('magazine.pillar.show', ['pillar' => 'bitcoin-money']).'" rel="canonical">', false);
});

test('pillar hubs are only indexable after six published posts', function () {
    $this->seed(CategorySeeder::class);
    $category = bitcoinMoneyCategory();
    publishPostsInCategory($category, 5);

    $this->get(route('magazine.pillar.show', ['pillar' => 'bitcoin-money']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);

    publishPostsInCategory($category, 1);

    $this->get(route('magazine.pillar.show', ['pillar' => 'bitcoin-money']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false);
});

test('localized pillar hubs retain localized canonical and alternate urls', function () {
    $this->seed(CategorySeeder::class);
    publishPostsInCategory(bitcoinMoneyCategory(), 6);

    $englishUrl = route('magazine.pillar.show', ['pillar' => 'bitcoin-money']);
    $germanUrl = route('magazine.localized.pillar.show', [
        'locale' => 'de',
        'pillar' => 'bitcoin-geld',
    ]);
    $fallbackUrl = route('magazine.pillar.show', ['pillar' => 'bitcoin-money']);

    $this->get($germanUrl)
        ->assertSuccessful()
        ->assertViewHas('locale', 'de')
        ->assertSee('<link href="'.$germanUrl.'" rel="canonical">', false)
        ->assertSee('href="'.$englishUrl.'"', false)
        ->assertSee('href="'.$germanUrl.'"', false)
        ->assertSee('href="'.$fallbackUrl.'" hreflang="x-default"', false);
});

test('an indexable pillar does not advertise a localized hub that remains noindex', function () {
    $this->seed(CategorySeeder::class);
    $category = bitcoinMoneyCategory();

    Post::factory()
        ->count(6)
        ->published()
        ->create([
            'category_id' => $category->id,
            'published_at' => now()->subMinute(),
        ])
        ->each(function (Post $post): void {
            PostTranslation::factory()->create([
                'post_id' => $post->id,
                'locale' => 'en',
                'title' => "English pillar post {$post->id}",
                'slug' => "english-pillar-post-{$post->id}",
            ]);
        });

    $germanUrl = route('magazine.localized.pillar.show', [
        'locale' => 'de',
        'pillar' => 'bitcoin-geld',
    ]);

    $this->get(route('magazine.pillar.show', ['pillar' => 'bitcoin-money']))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('href="'.route('magazine.pillar.show', ['pillar' => 'bitcoin-money']).'" hreflang="x-default"', false)
        ->assertDontSee('href="'.$germanUrl.'"', false);

    $this->get($germanUrl)
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('x-default uses an indexable localized pillar when the English hub remains noindex', function () {
    $this->seed(CategorySeeder::class);
    $category = bitcoinMoneyCategory();

    Post::factory()
        ->count(6)
        ->published()
        ->create([
            'category_id' => $category->id,
            'published_at' => now()->subMinute(),
        ])
        ->each(function (Post $post): void {
            PostTranslation::factory()->create([
                'post_id' => $post->id,
                'locale' => 'de',
                'title' => "Deutscher Pillar-Beitrag {$post->id}",
                'slug' => "deutscher-pillar-beitrag-{$post->id}",
            ]);
        });

    $germanUrl = route('magazine.localized.pillar.show', [
        'locale' => 'de',
        'pillar' => 'bitcoin-geld',
    ]);

    $this->get($germanUrl)
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('href="'.$germanUrl.'" hreflang="x-default"', false);
});

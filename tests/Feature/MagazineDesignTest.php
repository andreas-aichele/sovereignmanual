<?php

test('magazine index is the public start page', function () {
    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertSee('More room to act begins with clear next steps.');
});

test('german magazine index route stores the locale and renders an indexable page', function () {
    $this->get(route('magazine.localized.index', ['locale' => 'de']))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'de')
        ->assertSee('lang="de"', false)
        ->assertCookie('locale', 'de');
});

test('magazine article urls are available', function () {
    expect(route('magazine.localized.index', ['locale' => 'en'], absolute: false))->toBe('/en');
    expect(route('magazine.localized.index', ['locale' => 'de'], absolute: false))->toBe('/de');
    expect(route('magazine.category', ['category' => 'self-custody'], absolute: false))->toBe('/self-custody');
    expect(route('magazine.localized.category', ['locale' => 'de', 'category' => 'selbstverwahrung'], absolute: false))->toBe('/de/selbstverwahrung');
    expect(route('magazine.show', ['category' => 'self-custody', 'slug' => 'example'], absolute: false))->toBe('/self-custody/example');
    expect(route('magazine.localized.show', ['locale' => 'de', 'category' => 'selbstverwahrung', 'slug' => 'beispiel'], absolute: false))->toBe('/de/selbstverwahrung/beispiel');
});

test('admin backend and filament are not registered', function () {
    $composer = file_get_contents(base_path('composer.json'));
    $providers = file_get_contents(base_path('bootstrap/providers.php'));
    $user = file_get_contents(app_path('Models/User.php'));

    expect(app_path('Filament'))->not->toBeDirectory()
        ->and(app_path('Providers/Filament'))->not->toBeDirectory()
        ->and($composer)->not->toContain('filament/filament')
        ->and($providers)->not->toContain('AdminPanelProvider')
        ->and($user)->not->toContain('FilamentUser')
        ->and($user)->not->toContain('canAccessPanel');
});

test('frontend entry is a blade asset', function () {
    $contents = file_get_contents(resource_path('js/app.js'));

    expect($contents)->not->toContain('createInertiaApp')
        ->and($contents)->not->toContain("import '../css/app.css'")
        ->and($contents)->toContain("import('mermaid')")
        ->and($contents)->toContain('mermaid.initialize')
        ->and($contents)->toContain("querySelector: '.mermaid'")
        ->and($contents)->toContain('initializeAppearanceControls')
        ->and($contents)->toContain('initializeTableOfContentsScrollSpy')
        ->and($contents)->toContain('DOMContentLoaded');
});

test('frontend css loads before body scripts', function () {
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $vite = file_get_contents(base_path('vite.config.ts'));

    expect($vite)->toContain("input: ['resources/css/app.css', 'resources/js/app.js']")
        ->and($layout)->toContain("@vite('resources/css/app.css')")
        ->and($layout)->toContain("@vite('resources/js/app.js')")
        ->and(strpos($layout, "@vite('resources/css/app.css')"))->toBeLessThan(strpos($layout, '</head>'))
        ->and(strpos($layout, '</div>'))->toBeLessThan(strpos($layout, "@vite('resources/js/app.js')"))
        ->and(strpos($layout, "@vite('resources/js/app.js')"))->toBeLessThan(strpos($layout, '</body>'));
});

test('magazine localization strings live in language files', function () {
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));
    $englishTranslations = require lang_path('en/magazine.php');
    $germanTranslations = require lang_path('de/magazine.php');

    expect(lang_path('en/magazine.php'))->toBeReadableFile()
        ->and(lang_path('de/magazine.php'))->toBeReadableFile()
        ->and($englishTranslations)->toHaveKeys(['index', 'show', 'language_switcher', 'meta'])
        ->and($germanTranslations)->toHaveKeys(['index', 'show', 'language_switcher', 'meta'])
        ->and($englishTranslations)->not->toHaveKeys(['alternate_locale', 'locales'])
        ->and($germanTranslations)->not->toHaveKeys(['alternate_locale', 'locales'])
        ->and($englishTranslations)->not->toHaveKey('categories')
        ->and($germanTranslations)->not->toHaveKey('categories')
        ->and($englishTranslations['index'])->toHaveKeys(['heading', 'paths_heading', 'featured_heading', 'briefing_heading', 'view_pillar', 'waitlist'])
        ->and($germanTranslations['index'])->toHaveKeys(['heading', 'paths_heading', 'featured_heading', 'briefing_heading', 'view_pillar', 'waitlist'])
        ->and($englishTranslations['show'])->toHaveKeys(['alternate', 'breadcrumb_label', 'category', 'content_type', 'corrections', 'created', 'details', 'language', 'magazine', 'method', 'sources', 'toc', 'updated'])
        ->and($germanTranslations['show'])->toHaveKeys(['alternate', 'breadcrumb_label', 'category', 'content_type', 'corrections', 'created', 'details', 'language', 'magazine', 'method', 'sources', 'toc', 'updated'])
        ->and($controller)->not->toContain("locale === 'de'")
        ->and($controller)->not->toContain('Zurück zum Magazin')
        ->and($controller)->not->toContain('Back to magazine')
        ->and($controller)->not->toContain('Neuer Artikel')
        ->and($controller)->not->toContain('Latest article');
});

test('magazine start page presents three paths, selected briefings, and the waitlist', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));

    expect($index)->toContain('aria-labelledby="paths-heading"')
        ->and($index)->toContain('<h1')
        ->and($index)->toContain('$pillarSections')
        ->and($index)->toContain('$pillar[\'posts\']')
        ->and($index)->toContain("@include('magazine.partials.article-card'")
        ->and($index)->toContain('$briefings')
        ->and($index)->toContain('$copy[\'paths_heading\']')
        ->and($index)->toContain('$copy[\'briefing_heading\']')
        ->and($index)->toContain('$copy[\'waitlist\']')
        ->and($index)->toContain("route('waitlist.store')")
        ->and($index)->toContain('name="consent"')
        ->and($index)->toContain('bitcoin-pillar-accent')
        ->and($index)->toContain('btn btn-primary btn-sm')
        ->and($index)->not->toContain('$copy[\'about_heading\']');
});

test('magazine listing uses responsive editorial article cards', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $card = file_get_contents(resource_path('views/magazine/partials/article-card.blade.php'));
    $image = file_get_contents(resource_path('views/components/img.blade.php'));

    expect($index)->toContain('lg:card-side')
        ->and($index)->toContain('aspect-4/3')
        ->and($index)->toContain("@include('magazine.partials.article-card'")
        ->and($index)->toContain('lg:grid-cols-3')
        ->and($card)->toContain('card-side')
        ->and($card)->toContain('md:flex-col')
        ->and($card)->toContain('aspect-square w-2/5')
        ->and($card)->toContain('card-body w-3/5')
        ->and($card)->toContain('max-md:hidden')
        ->and($card)->toContain('(min-width: 48rem) 33vw, 40vw')
        ->and($image)->toContain('<picture class="block h-full w-full">');
});

test('public navigation uses a scalable language dropdown', function () {
    $nav = file_get_contents(resource_path('views/components/public-nav.blade.php'));
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));

    expect($nav)->toContain("'pillarNavItems' => []")
        ->and($nav)->toContain("'categoryNavItems' => []")
        ->and($nav)->toContain('$locale ??= \App\Support\Locales::fallback();')
        ->and($nav)->toContain('<details class="dropdown dropdown-end">')
        ->and($nav)->toContain('dropdown-content menu')
        ->and($nav)->toContain('@foreach ($languageOptions as $option)')
        ->and($nav)->toContain('svg(\'lucide-chevron-down\'')
        ->and($nav)->toContain('magazine.language_switcher')
        ->and($nav)->not->toContain('aria-hidden="true">v</span>')
        ->and($nav)->not->toContain('alternateLocale')
        ->and($index)->toContain(':language-options="$languageOptions"')
        ->and($show)->toContain(':language-options="$languageOptions"')
        ->and($controller)->toContain('private function languageOptions')
        ->and($controller)->toContain('Locales::supported()')
        ->and($controller)->toContain('Locales::language($locale)->nativeName()');
});

test('public navigation prioritizes pillar links and falls back to categories', function () {
    $categoryNavItems = [
        ['label' => 'Self Custody', 'url' => '/self-custody'],
    ];
    $pillarNavItems = [
        ['label' => 'Digital Sovereignty', 'url' => '/digital-sovereignty'],
    ];

    $this->blade(
        '<x-public-nav locale="en" :category-nav-items="$categoryNavItems" :pillar-nav-items="$pillarNavItems" />',
        compact('categoryNavItems', 'pillarNavItems'),
    )
        ->assertSee('Topics')
        ->assertSee('Digital Sovereignty')
        ->assertDontSee('Self Custody')
        ->assertSee('aria-label="Sovereign Manual"', false);

    $this->blade(
        '<x-public-nav locale="en" :category-nav-items="$categoryNavItems" :pillar-nav-items="$pillarNavItems" />',
        [
            'categoryNavItems' => $categoryNavItems,
            'pillarNavItems' => [],
        ],
    )
        ->assertSee('Categories')
        ->assertSee('Self Custody');
});

test('magazine headings can break long words', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $css = file_get_contents(resource_path('css/content.css'));

    expect($index)->toContain('wrap-anywhere')
        ->and($index)->toContain('text-3xl')
        ->and($index)->toContain('card-title')
        ->and($show)->toContain('wrap-anywhere')
        ->and($show)->toContain('text-4xl')
        ->and($css)->toContain('@apply hyphens-auto')
        ->and($css)->toContain('overflow-wrap: anywhere;');
});

test('public magazine css no longer ships legacy background stylesheet', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect(resource_path('css/background.css'))->toBeFile()
        ->and($css)->toContain("@import './background.css'")
        ->and($css)->toContain("@import './content.css'");
});

test('magazine article layout keeps public container width and readable article content', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));

    expect($index)->toContain('max-w-6xl px-4 py-10 sm:px-6 lg:px-8')
        ->and($show)->toContain('max-w-6xl px-4 py-10 sm:px-6 lg:px-8')
        ->and($show)->toContain('header class="max-w-3xl"')
        ->and($show)->toContain('lg:grid-cols-[minmax(0,48rem)_16rem]')
        ->and($show)->toContain('lg:sticky lg:top-8')
        ->and($show)->toContain('border-base-300 mt-10 border-t pt-8 text-sm')
        ->and($show)->toContain('content-body max-w-none');
});

test('magazine article renders linked table of contents for mobile and desktop', function () {
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));
    $css = file_get_contents(resource_path('css/content.css'));

    expect($show)->toContain('collapse')
        ->and($show)->toContain('collapse-arrow')
        ->and($show)->toContain('collapse-title')
        ->and($show)->toContain('collapse-content')
        ->and($show)->toContain('menu menu-sm')
        ->and($show)->toContain('lg:hidden')
        ->and($show)->toContain('<summary')
        ->and($show)->toContain('{{ $copy[\'toc\'] }}')
        ->and($show)->toContain('@foreach ($post[\'toc\'] as $item)')
        ->and($show)->toContain('href="#{{ $item[\'id\'] }}"')
        ->and($controller)->toContain('uniqueHeadingId')
        ->and($controller)->not->toContain('renderMarkdownWithTableOfContents')
        ->and($css)->toContain('.content-body h2[id]')
        ->and($css)->toContain('scroll-mt-24');
});

test('magazine article navigation uses breadcrumbs instead of a back button', function () {
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $breadcrumbs = file_get_contents(resource_path('views/components/breadcrumbs.blade.php'));

    expect($show)->toContain('<x-breadcrumbs :label="$copy[\'breadcrumb_label\']"')
        ->and($breadcrumbs)->toContain('aria-current="page"')
        ->and($breadcrumbs)->toContain('<ol class="wrap-break-word hyphens-auto">')
        ->and($breadcrumbs)->toContain('<li class="inline">')
        ->and($breadcrumbs)->not->toContain('flex')
        ->and($breadcrumbs)->not->toContain('truncate')
        ->and($show)->toContain("route('magazine.localized.index', ['locale' => \$locale])")
        ->and($show)->not->toContain('$copy[\'back\']');
});

test('magazine article tables have readable cell spacing', function () {
    $css = file_get_contents(resource_path('css/content.css'));

    expect($css)->toContain('.content-body table')
        ->and($css)->toContain('overflow-x-auto')
        ->and($css)->toContain('.content-body th,')
        ->and($css)->toContain('.content-body td')
        ->and($css)->toContain('px-4 py-3');
});

test('magazine article diagrams have responsive readable styling', function () {
    $css = file_get_contents(resource_path('css/content.css'));
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));

    expect($css)->toContain('.content-body .mermaid')
        ->and($css)->toContain('overflow-x-auto')
        ->and($css)->toContain('.content-body .mermaid svg')
        ->and($controller)->toContain('renderFlowDiagramData')
        ->and($controller)->toContain('flowDiagramRows')
        ->and($controller)->toContain('mermaidFlowchart');
});

test('public magazine uses a calm editorial visual system with light and dark themes', function () {
    $theme = file_get_contents(resource_path('css/theme.css'));
    $background = file_get_contents(resource_path('css/background.css'));
    $javascript = file_get_contents(resource_path('js/app.js'));
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $nav = file_get_contents(resource_path('views/components/public-nav.blade.php'));
    $logo = file_get_contents(public_path('logo.svg'));

    expect($theme)->toContain("name: 'editorial-light'")
        ->and($theme)->toContain("name: 'editorial-dark'")
        ->and($theme)->not->toContain('synthwave')
        ->and($theme)->not->toContain('#f7931a')
        ->and($background)->toContain('radial-gradient(')
        ->and($background)->toContain('ellipse at 4% 0%')
        ->and($background)->toContain('ellipse at 96% 12%')
        ->and($background)->not->toContain('body::before')
        ->and($background)->not->toContain('background-size: 4rem 4rem')
        ->and($javascript)->toContain("'editorial-light'")
        ->and($javascript)->toContain("'editorial-dark'")
        ->and($javascript)->toContain('data-appearance-toggle')
        ->and($javascript)->not->toContain('#f7931a')
        ->and($layout)->toContain('data-appearance="{{ $appearance }}"')
        ->and($layout)->toContain('data-theme="{{ $isDarkAppearance ? \'editorial-dark\' : \'editorial-light\' }}"')
        ->and($layout)->not->toContain('synthwave')
        ->and($index)->toContain('card card-border bg-base-200')
        ->and($index)->toContain('bitcoin-pillar-accent')
        ->and($show)->toContain('card card-border bg-base-300')
        ->and($nav)->toContain('navbar bg-base-100/85')
        ->and($nav)->toContain("'pillarNavItems' => []")
        ->and($nav)->toContain("'categoryNavItems' => []")
        ->and($nav)->toContain('magazine.nav.topics')
        ->and($nav)->toContain('data-appearance-toggle')
        ->and($nav)->toContain('magazine.nav.theme')
        ->and($logo)->toContain('<circle')
        ->and($logo)->not->toContain('bitcoin')
        ->and($logo)->not->toContain('F7931A');
});

test('appearance cookie selects the matching editorial theme', function () {
    $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertSee('data-appearance="light"', false)
        ->assertSee('data-theme="editorial-light"', false);

    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertSee('data-appearance="dark"', false)
        ->assertSee('data-theme="editorial-dark"', false)
        ->assertSee('class="dark"', false);

    $this->withUnencryptedCookie('appearance', 'unexpected')
        ->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertSee('data-appearance="system"', false)
        ->assertSee('data-theme="editorial-light"', false);
});

test('frontend prefers daisyUI components and semantic theme colors', function () {
    $agents = file_get_contents(base_path('AGENTS.md'));
    $auth = file_get_contents(resource_path('views/auth/login.blade.php'));
    $settings = file_get_contents(resource_path('views/settings/profile.blade.php'));
    $settingsLayout = file_get_contents(resource_path('views/components/settings-layout.blade.php'));
    $views = collect(glob(resource_path('views/**/*.blade.php')))
        ->merge(glob(resource_path('views/**/**/*.blade.php')))
        ->unique()
        ->map(fn (string $file): string => file_get_contents($file))
        ->implode("\n");

    expect($agents)->toContain('Think in daisyUI components first')
        ->and($agents)->toContain('Keep colors and visual accents theme-driven')
        ->and($auth)->toContain('class="input w-full"')
        ->and($auth)->toContain('class="btn btn-primary btn-block"')
        ->and($settings)->toContain('card card-border bg-base-200')
        ->and($settings)->toContain('alert alert-success alert-soft')
        ->and($settingsLayout)->toContain('class="menu bg-base-200 rounded-box w-full"')
        ->and($views)->not->toMatch('/(?:bg|text|border|shadow|ring)-(?:slate|gray|zinc|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|black|white)(?:-|\\/|\\b)/');
});

test('public magazine frontend does not link login admin or unsplash', function () {
    $publicFiles = [
        resource_path('views/components/public-nav.blade.php'),
        resource_path('views/magazine/index.blade.php'),
        resource_path('views/magazine/show.blade.php'),
    ];

    foreach ($publicFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('/login')
            ->and($contents)->not->toContain('Admin')
            ->and($contents)->not->toContain('unsplash');
    }
});

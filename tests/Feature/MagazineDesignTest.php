<?php

test('magazine index is the public start page', function () {
    $this->get(route('magazine.index'))
        ->assertSuccessful()
        ->assertViewIs('magazine.index')
        ->assertViewHas('locale', 'en')
        ->assertSee('Sovereign Manual Magazine');
});

test('german magazine index route stores the locale and redirects to the start page', function () {
    $this->get(route('magazine.localized.index', ['locale' => 'de']))
        ->assertRedirect(route('magazine.localized.index', ['locale' => 'de']))
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
        ->and($englishTranslations)->toHaveKeys(['index', 'show', 'language_switcher', 'locales', 'meta'])
        ->and($germanTranslations)->toHaveKeys(['index', 'show', 'language_switcher', 'locales', 'meta'])
        ->and($englishTranslations)->not->toHaveKey('categories')
        ->and($germanTranslations)->not->toHaveKey('categories')
        ->and($englishTranslations['index'])->toHaveKeys(['about_body', 'about_heading'])
        ->and($germanTranslations['index'])->toHaveKeys(['about_body', 'about_heading'])
        ->and($englishTranslations['show'])->toHaveKeys(['alternate', 'breadcrumb_label', 'category', 'details', 'language', 'magazine', 'toc'])
        ->and($germanTranslations['show'])->toHaveKeys(['alternate', 'breadcrumb_label', 'category', 'details', 'language', 'magazine', 'toc'])
        ->and($controller)->not->toContain("locale === 'de'")
        ->and($controller)->not->toContain('Zurück zum Magazin')
        ->and($controller)->not->toContain('Back to magazine')
        ->and($controller)->not->toContain('Neuer Artikel')
        ->and($controller)->not->toContain('Latest article');
});

test('magazine start page moves visible intro copy below article list as about section', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));

    expect($index)->toContain('<section class="sr-only">')
        ->and($index)->toContain('$copy[\'about_heading\']')
        ->and($index)->toContain('$copy[\'about_body\']')
        ->and($index)->toContain('border-primary/20 mt-14 border-t pt-10')
        ->and($index)->not->toContain('$copy[\'featured\']');
});

test('public navigation uses a scalable language dropdown', function () {
    $nav = file_get_contents(resource_path('views/components/public-nav.blade.php'));
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));

    expect($nav)->toContain('@props([\'locale\' => \'en\', \'languageOptions\' => []])')
        ->and($nav)->toContain('<details class="group relative">')
        ->and($nav)->toContain('@foreach ($languageOptions as $option)')
        ->and($nav)->toContain('svg(\'lucide-chevron-down\'')
        ->and($nav)->toContain('magazine.language_switcher')
        ->and($nav)->not->toContain('aria-hidden="true">v</span>')
        ->and($nav)->not->toContain('alternateLocale')
        ->and($index)->toContain(':language-options="$languageOptions"')
        ->and($show)->toContain(':language-options="$languageOptions"')
        ->and($controller)->toContain('private function languageOptions')
        ->and($controller)->toContain('$this->translationArray(\'locales\', $currentLocale)');
});

test('magazine headings can break long words', function () {
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $css = file_get_contents(resource_path('css/content.css'));

    expect($index)->toContain('wrap-anywhere')
        ->and($index)->toContain('text-3xl')
        ->and($index)->toContain('text-xl')
        ->and($show)->toContain('wrap-anywhere')
        ->and($show)->toContain('text-4xl')
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
        ->and($show)->toContain('hidden text-sm lg:sticky')
        ->and($show)->toContain('content-body max-w-none');
});

test('magazine article renders linked table of contents for mobile and desktop', function () {
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/MagazineController.php'));
    $css = file_get_contents(resource_path('css/content.css'));

    expect($show)->toContain('mt-8 rounded-lg border p-4 text-sm lg:hidden')
        ->and($show)->toContain('lg:hidden')
        ->and($show)->toContain('<summary')
        ->and($show)->toContain('{{ $copy[\'toc\'] }}')
        ->and($show)->toContain('@foreach ($post[\'toc\'] as $item)')
        ->and($show)->toContain('href="#{{ $item[\'id\'] }}"')
        ->and($controller)->toContain('renderMarkdownWithTableOfContents')
        ->and($controller)->toContain('uniqueHeadingId')
        ->and($css)->toContain('.content-body h2[id]')
        ->and($css)->toContain('scroll-mt-24');
});

test('magazine article navigation uses breadcrumbs instead of a back button', function () {
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));

    expect($show)->toContain('aria-label="{{ $copy[\'breadcrumb_label\'] }}"')
        ->and($show)->toContain('aria-current="page"')
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
        ->and($controller)->toContain('renderAsciiDiagramCodeBlocks')
        ->and($controller)->toContain('renderFlowDiagramData')
        ->and($controller)->toContain('mermaidFlowchart');
});

test('public magazine styles restore synthwave atmosphere without removing readable panels', function () {
    $css = file_get_contents(resource_path('css/background.css'));
    $index = file_get_contents(resource_path('views/magazine/index.blade.php'));
    $show = file_get_contents(resource_path('views/magazine/show.blade.php'));
    $nav = file_get_contents(resource_path('views/components/public-nav.blade.php'));

    expect($css)->toContain('radial-gradient(')
        ->and($css)->toContain('circle at 12% 8%')
        ->and($css)->toContain('circle at 86% 18%')
        ->and($css)->toContain('body::before')
        ->and($css)->toContain('background-size: 4rem 4rem')
        ->and($index)->toContain('bg-base-200/90')
        ->and($index)->toContain('ring-cyan-300/10')
        ->and($show)->toContain('bg-base-300 mt-8 overflow-hidden')
        ->and($nav)->toContain('border-primary/20 bg-base-100/85');
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

<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']"
    :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']" :og-locale="$meta['ogLocale']"
    :og-locale-alternates="$meta['ogLocaleAlternates']" :robots="$meta['robots']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumbs :label="__('magazine.show.breadcrumb_label', [], $locale)" :items="[
            [
                'label' => __('magazine.show.magazine', [], $locale),
                'url' => $locale === \App\Support\Locales::fallback()
                    ? route('magazine.index')
                    : route('magazine.localized.index', ['locale' => $locale]),
            ],
            ['label' => $pillar['title'], 'current' => true],
        ]" />

        <header class="mt-8 max-w-3xl">
            <p class="text-primary text-sm font-semibold uppercase tracking-[0.2em]">
                {{ __('magazine.nav.topics', [], $locale) }}</p>
            <h1 class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $pillar['title'] }}</h1>

            @if ($pillar['description'] !== '')
                <div class="content-body text-base-content/75 mt-5 text-lg leading-8">
                    {!! Illuminate\Support\Str::markdown($pillar['description']) !!}
                </div>
            @endif
        </header>

        @if ($posts->isEmpty())
            <div class="alert mt-10">{{ $copy['empty'] }}</div>
        @else
            <section class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a class="card card-border bg-base-200 h-full overflow-hidden shadow-sm transition-shadow hover:shadow-lg"
                        href="{{ $post['url'] }}">
                        <article>
                            <figure class="bg-base-300 aspect-16/10">
                                <x-img class="h-full w-full object-cover" :src="$post['image']"
                                    :alt="$post['image_alt'] ?? $post['title']" :responsive="$post['image_responsive']"
                                    sizes="(min-width: 64rem) 22rem, (min-width: 40rem) 50vw, 100vw" />
                            </figure>

                            <div class="card-body gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <span class="badge badge-outline badge-sm">{{ $post['content_type_label'] }}</span>
                                    <span class="badge badge-ghost badge-sm">{{ $post['category_label'] }}</span>
                                </div>
                                <h2 class="card-title wrap-anywhere leading-tight">{{ $post['title'] }}</h2>

                                @if ($post['excerpt'])
                                    <p class="text-base-content/70 line-clamp-3 text-sm">{{ $post['excerpt'] }}</p>
                                @endif
                            </div>
                        </article>
                    </a>
                @endforeach
            </section>

            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </main>
</x-layouts.app>

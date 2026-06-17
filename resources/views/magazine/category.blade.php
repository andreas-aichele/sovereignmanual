<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']"
    :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumbs :label="__('magazine.show.breadcrumb_label', [], $locale)" :items="[
            [
                'label' => __('magazine.show.magazine', [], $locale),
                'url' => route('magazine.index'),
            ],
            ['label' => $category['title'], 'current' => true],
        ]" />

        <header class="mt-8 max-w-3xl">
            <h1
                class="wrap-anywhere text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $category['title'] }}</h1>

            @if ($category['description'] !== '')
                <p class="text-base-content/75 mt-5 text-lg leading-8">
                    {!! Illuminate\Support\Str::markdown($category['description']) !!}</p>
            @endif
        </header>

        @if ($posts->isEmpty())
            <div
                class="bg-base-200 text-base-content/70 mt-10 rounded-lg border border-white/10 p-8">
                {{ $copy['empty'] }}
            </div>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ $post['url'] }}">
                        <article
                            class="border-primary/15 bg-base-200/90 hover:border-primary/40 overflow-hidden rounded-lg border-2 shadow-lg shadow-fuchsia-950/10 transition hover:shadow-xl hover:shadow-fuchsia-950/40">
                            <div class="aspect-16/10 bg-base-300">
                                <x-img class="h-full w-full object-cover"
                                    :src="$post['image']" :alt="$post['image_alt'] ??
                                        $post['title']"
                                    :responsive="$post['image_responsive']"
                                    sizes="(min-width: 64rem) 22rem, (min-width: 40rem) 50vw, 100vw" />
                            </div>

                            <div class="p-5">
                                <p
                                    class="text-primary text-xs font-semibold uppercase tracking-[0.2em]">
                                    {{ $post['category_label'] }}</p>
                                <h2
                                    class="wrap-anywhere mt-3 text-xl font-semibold leading-tight">
                                    {{ $post['title'] }}</h2>

                                @if ($post['excerpt'])
                                    <p
                                        class="text-base-content/70 mt-3 line-clamp-3 text-sm">
                                        {{ $post['excerpt'] }}</p>
                                @endif
                            </div>
                        </article>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </main>
</x-layouts.app>

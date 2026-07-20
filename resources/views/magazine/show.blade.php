<x-layouts.app :title="$meta['title']" :description="$meta['description']" :keywords="$meta['keywords']"
    :canonical="$meta['canonical']" :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']"
    :og-locale="$meta['ogLocale']" :og-locale-alternates="$meta['ogLocaleAlternates']" :og-image="$meta['ogImage']" :author="$meta['author']"
    :article-published-time="$meta['articlePublishedTime']" :article-modified-time="$meta['articleModifiedTime']" :article-section="$meta['articleSection']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumbs :label="$copy['breadcrumb_label']" :items="[
            [
                'label' => $copy['magazine'],
                'url' => $locale === \App\Support\Locales::fallback()
                    ? route('magazine.index')
                    : route('magazine.localized.index', ['locale' => $locale]),
            ],
            [
                'label' => $post['category_label'],
                'url' => $post['category_url'],
            ],
            ['label' => $post['title'], 'current' => true],
        ]" />

        <article class="mt-8">
            <header class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <a class="text-primary text-sm font-semibold uppercase tracking-[0.2em] underline-offset-4 hover:underline"
                        href="{{ $post['category_url'] }}">
                        {{ $post['category_label'] }}</a>
                    <span class="badge badge-outline badge-sm">{{ $post['content_type_label'] }}</span>
                </div>
                <h1
                    class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-4xl">
                    {{ $post['title'] }}</h1>

                @if ($post['excerpt'])
                    <p class="text-base-content/70 mt-5 text-xl">
                        {{ $post['excerpt'] }}</p>
                @endif
            </header>

            <figure
                class="card card-border bg-base-300 mt-8 overflow-hidden shadow-xl">
                <x-img class="max-h-[32rem] w-full object-cover"
                    :src="$post['image']" :alt="$post['image_alt'] ?? $post['title']" :responsive="$post['image_responsive']"
                    sizes="(min-width: 72rem) 72rem, 100vw" hero />
            </figure>

            @if (count($post['toc']) > 0)
                <details
                    class="collapse-arrow bg-base-200 border-base-300 collapse mt-8 border text-sm lg:hidden">
                    <summary
                        class="collapse-title text-primary font-semibold uppercase tracking-[0.2em]">
                        {{ $copy['toc'] }}</summary>

                    <div class="collapse-content">
                        <ol class="menu menu-sm w-full">
                            @foreach ($post['toc'] as $item)
                                <li
                                    class="{{ $item['level'] === 3 ? 'ml-4' : '' }}">
                                    <a class="aria-[current=location]:text-primary aria-[current=location]:font-semibold"
                                        data-toc-link
                                        href="#{{ $item['id'] }}">
                                        {{ $item['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </details>
            @endif

            <div
                class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,48rem)_16rem] lg:items-start lg:gap-14">
                <div class="min-w-0">
                    <div class="content-body max-w-none">
                        {!! $post['html'] !!}
                    </div>

                    @foreach ($post['blocks'] as $block)
                        <section @class([
                            'content-body max-w-none',
                            'mt-10' => in_array($block['type'], ['section', 'markdown'], true),
                            'mt-6' => !in_array($block['type'], ['section', 'markdown'], true),
                        ])>
                            @if ($block['type'] === 'image' && $block['asset'])
                                <figure class="m-0">
                                    <x-img
                                        class="rounded-box border-base-300 border"
                                        :src="$block['asset']['url']" :alt="$block['asset']['alt'] ?? ''"
                                        :responsive="$block['asset'][
                                            'responsive'
                                        ]"
                                        sizes="(min-width: 64rem) 48rem, 100vw" />

                                    {!! $block['html'] !!}
                                </figure>
                            @else
                                {!! $block['html'] !!}
                            @endif
                        </section>
                    @endforeach
                </div>

                <aside
                    class="border-base-300 mt-10 border-t pt-8 text-sm lg:sticky lg:top-8 lg:mt-0 lg:border-t-0 lg:border-l lg:pl-6 lg:pt-0">
                    @if (count($post['toc']) > 0)
                        <nav class="mb-8 hidden lg:block" aria-label="{{ $copy['toc'] }}">
                            <p
                                class="text-primary font-semibold uppercase tracking-[0.2em]">
                                {{ $copy['toc'] }}</p>

                            <ol class="menu menu-sm mt-2 w-full">
                                @foreach ($post['toc'] as $item)
                                    <li
                                        class="{{ $item['level'] === 3 ? 'ml-4' : '' }}">
                                        <a class="aria-[current=location]:text-primary aria-[current=location]:font-semibold"
                                            data-toc-link
                                            href="#{{ $item['id'] }}">
                                            {{ $item['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <p
                        class="text-primary font-semibold uppercase tracking-[0.2em]">
                        {{ $copy['details'] }}</p>

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt
                                class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                {{ $copy['category'] }}</dt>
                            <dd class="mt-2">
                                <a class="text-base-content/85 hover:text-primary underline-offset-4 hover:underline"
                                    href="{{ $post['category_url'] }}">{{ $post['category_label'] }}</a>
                            </dd>
                        </div>

                        <div>
                            <dt
                                class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                {{ $copy['content_type'] }}</dt>
                            <dd class="text-base-content/85 mt-2">{{ $post['content_type_label'] }}</dd>
                        </div>

                        @if ($post['created_at'])
                            <div>
                                <dt
                                    class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                    {{ $copy['created'] }}</dt>
                                <dd class="text-base-content/85 mt-2">
                                    <time datetime="{{ $post['created_at'] }}">{{ $post['created_at'] }}</time>
                                </dd>
                            </div>
                        @endif

                        @if ($post['substantially_updated_at'])
                            <div>
                                <dt
                                    class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                    {{ $copy['updated'] }}</dt>
                                <dd class="text-base-content/85 mt-2">
                                    <time datetime="{{ $post['substantially_updated_at'] }}">{{ $post['substantially_updated_at'] }}</time>
                                </dd>
                            </div>
                        @endif

                        <div>
                            <dt
                                class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                {{ $copy['method'] }}</dt>
                            <dd class="text-base-content/85 mt-2">
                                {{ $post['method'] ? $copy['method_value'] : $copy['method_unrecorded'] }}
                            </dd>
                        </div>

                        @if ($meta['alternate'])
                            <div>
                                <dt
                                    class="text-base-content/45 text-xs font-semibold uppercase tracking-[0.18em]">
                                    {{ $copy['language'] }}</dt>
                                <dd class="mt-2">
                                    <a class="text-primary font-semibold underline-offset-4 hover:underline"
                                        href="{{ $meta['alternate'] }}">{{ $copy['alternate'] }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if (count($post['sources']) > 0)
                        <section class="border-base-300 mt-8 border-t pt-6" aria-labelledby="article-sources">
                            <h2 id="article-sources" class="text-primary text-xs font-semibold uppercase tracking-[0.18em]">
                                {{ $copy['sources'] }}</h2>
                            <ol class="mt-3 space-y-3">
                                @foreach ($post['sources'] as $source)
                                    <li>
                                        <a class="text-base-content/85 font-medium underline-offset-4 hover:text-primary hover:underline"
                                            href="{{ $source['url'] }}" rel="noopener noreferrer" target="_blank">
                                            {{ $source['title'] }}</a>
                                        @if ($source['publisher'] || $source['published_at'])
                                            <p class="text-base-content/55 mt-1 text-xs">
                                                {{ collect([$source['publisher'], $source['published_at']])->filter()->implode(' · ') }}
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    <section class="border-base-300 mt-8 border-t pt-6" aria-labelledby="article-corrections">
                        <h2 id="article-corrections" class="text-primary text-xs font-semibold uppercase tracking-[0.18em]">
                            {{ $copy['corrections'] }}</h2>
                        <a class="text-base-content/85 mt-3 inline-block font-medium underline-offset-4 hover:text-primary hover:underline"
                            href="https://github.com/andreas-aichele/sovereignmanual/issues/new" rel="noopener noreferrer" target="_blank">
                            {{ $copy['report_correction'] }}</a>
                    </section>
                </aside>
            </div>
        </article>
    </main>
</x-layouts.app>

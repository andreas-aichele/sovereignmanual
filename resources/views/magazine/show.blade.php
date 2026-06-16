<x-layouts.app :title="$meta['title']" :description="$meta['description']" :keywords="$meta['keywords']"
    :canonical="$meta['canonical']" :alternate="$meta['alternate']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <nav aria-label="{{ $copy['breadcrumb_label'] }}"
            class="text-sm text-base-content/65">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('magazine.index') }}"
                        class="font-semibold text-primary underline-offset-4 hover:underline">{{ $copy['magazine'] }}</a>
                </li>
                <li aria-hidden="true" class="text-base-content/35">/</li>
                <li>
                    <a href="{{ $post['category_url'] }}"
                        class="text-base-content/80 underline-offset-4 hover:text-primary hover:underline">{{ $post['category_label'] }}</a>
                </li>
                <li aria-hidden="true" class="text-base-content/35">/</li>
                <li aria-current="page"
                    class="max-w-full truncate text-base-content sm:max-w-96">
                    {{ $post['title'] }}</li>
            </ol>
        </nav>

        <article class="mt-8">
            <header class="max-w-3xl">
                <a href="{{ $post['category_url'] }}"
                    class="text-sm font-semibold uppercase tracking-[0.2em] text-primary underline-offset-4 hover:underline">
                    {{ $post['category_label'] }}</a>
                <h1
                    class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-6xl">
                    {{ $post['title'] }}</h1>

                @if ($post['excerpt'])
                    <p class="mt-5 text-xl text-base-content/70">
                        {{ $post['excerpt'] }}</p>
                @endif
            </header>

            <div
                class="mt-8 overflow-hidden rounded-lg border border-primary/20 bg-base-300 shadow-2xl shadow-fuchsia-950/20 ring-1 ring-cyan-300/10">
                <img src="{{ $post['image'] }}"
                    alt="{{ $post['image_alt'] ?? $post['title'] }}"
                    class="max-h-[32rem] w-full object-cover">
            </div>

            @if (count($post['toc']) > 0)
                <details
                    class="mt-8 rounded-lg border border-primary/20 bg-base-200/90 p-4 text-sm open:border-primary/50 lg:hidden">
                    <summary
                        class="cursor-pointer select-none font-semibold uppercase tracking-[0.2em] text-primary">
                        {{ $copy['toc'] }}</summary>

                    <ol class="mt-4 space-y-3">
                        @foreach ($post['toc'] as $item)
                            <li
                                class="{{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                <a href="#{{ $item['id'] }}" data-toc-link
                                    class="block leading-snug text-base-content/75 underline-offset-4 transition hover:text-primary hover:underline aria-[current=location]:font-semibold aria-[current=location]:text-primary">
                                    {{ $item['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </details>
            @endif

            <div
                class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,48rem)_16rem] lg:items-start lg:gap-14">
                <div class="min-w-0">
                    <div class="content-body max-w-none">
                        {!! $post['html'] !!}
                    </div>

                    @foreach ($post['blocks'] as $block)
                        <section class="content-body mt-10 max-w-none">
                            @if ($block['asset'])
                                <img src="{{ $block['asset']['url'] }}"
                                    alt="{{ $block['asset']['alt'] ?? '' }}"
                                    class="rounded-lg border border-white/10">
                            @endif

                            {!! $block['html'] !!}
                        </section>
                    @endforeach
                </div>

                <aside
                    class="hidden text-sm lg:sticky lg:top-8 lg:block lg:border-l lg:pl-6">
                    @if (count($post['toc']) > 0)
                        <nav aria-label="{{ $copy['toc'] }}" class="mb-8">
                            <p
                                class="font-semibold uppercase tracking-[0.2em] text-primary">
                                {{ $copy['toc'] }}</p>

                            <ol class="mt-4 space-y-3">
                                @foreach ($post['toc'] as $item)
                                    <li
                                        class="{{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                        <a href="#{{ $item['id'] }}"
                                            data-toc-link
                                            class="block leading-snug text-base-content/70 underline-offset-4 transition hover:text-primary hover:underline aria-[current=location]:font-semibold aria-[current=location]:text-primary">
                                            {{ $item['title'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-primary">
                        {{ $copy['details'] }}</p>

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/45">
                                {{ $copy['category'] }}</dt>
                            <dd class="mt-2">
                                <a href="{{ $post['category_url'] }}"
                                    class="text-base-content/85 underline-offset-4 hover:text-primary hover:underline">{{ $post['category_label'] }}</a>
                            </dd>
                        </div>

                        @if ($meta['alternate'])
                            <div>
                                <dt
                                    class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/45">
                                    {{ $copy['language'] }}</dt>
                                <dd class="mt-2">
                                    <a href="{{ $meta['alternate'] }}"
                                        class="font-semibold text-primary underline-offset-4 hover:underline">{{ $copy['alternate'] }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </aside>
            </div>
        </article>
    </main>
</x-layouts.app>

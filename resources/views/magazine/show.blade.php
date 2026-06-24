<x-layouts.app :title="$meta['title']" :description="$meta['description']" :keywords="$meta['keywords']"
    :canonical="$meta['canonical']" :alternate="$meta['alternate']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumbs :label="$copy['breadcrumb_label']" :items="[
            ['label' => $copy['magazine'], 'url' => route('magazine.index')],
            ['label' => $post['category_label'], 'url' => $post['category_url']],
            ['label' => $post['title'], 'current' => true],
        ]" />

        <article class="mt-8">
            <header class="max-w-3xl">
                <a class="text-primary text-sm font-semibold uppercase tracking-[0.2em] underline-offset-4 hover:underline"
                    href="{{ $post['category_url'] }}">
                    {{ $post['category_label'] }}</a>
                <h1
                    class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-4xl">
                    {{ $post['title'] }}</h1>

                @if ($post['excerpt'])
                    <p class="text-base-content/70 mt-5 text-xl">
                        {{ $post['excerpt'] }}</p>
                @endif
            </header>

            <div
                class="border-primary/20 bg-base-300 mt-8 overflow-hidden rounded-lg border shadow-2xl shadow-fuchsia-950/20 ring-1 ring-cyan-300/10">
                <x-img class="max-h-[32rem] w-full object-cover"
                    :src="$post['image']"
                    :alt="$post['image_alt'] ?? $post['title']"
                    :responsive="$post['image_responsive']"
                    sizes="(min-width: 72rem) 72rem, 100vw" hero />
            </div>

            @if (count($post['toc']) > 0)
                <details
                    class="border-primary/20 bg-base-200/90 open:border-primary/50 mt-8 rounded-lg border p-4 text-sm lg:hidden">
                    <summary
                        class="text-primary cursor-pointer select-none font-semibold uppercase tracking-[0.2em]">
                        {{ $copy['toc'] }}</summary>

                    <ol class="mt-4 space-y-3">
                        @foreach ($post['toc'] as $item)
                            <li
                                class="{{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                <a class="text-base-content/75 hover:text-primary aria-[current=location]:text-primary block leading-snug underline-offset-4 transition hover:underline aria-[current=location]:font-semibold"
                                    data-toc-link href="#{{ $item['id'] }}">
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
                                <x-img class="rounded-lg border border-white/10"
                                    :src="$block['asset']['url']"
                                    :alt="$block['asset']['alt'] ?? ''"
                                    :responsive="$block['asset']['responsive']"
                                    sizes="(min-width: 64rem) 48rem, 100vw" />
                            @endif

                            {!! $block['html'] !!}
                        </section>
                    @endforeach
                </div>

                <aside
                    class="hidden text-sm lg:sticky lg:top-8 lg:block lg:border-l lg:pl-6">
                    @if (count($post['toc']) > 0)
                        <nav class="mb-8" aria-label="{{ $copy['toc'] }}">
                            <p
                                class="text-primary font-semibold uppercase tracking-[0.2em]">
                                {{ $copy['toc'] }}</p>

                            <ol class="mt-4 space-y-3">
                                @foreach ($post['toc'] as $item)
                                    <li
                                        class="{{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                        <a class="text-base-content/70 hover:text-primary aria-[current=location]:text-primary block leading-snug underline-offset-4 transition hover:underline aria-[current=location]:font-semibold"
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
                </aside>
            </div>
        </article>
    </main>
</x-layouts.app>

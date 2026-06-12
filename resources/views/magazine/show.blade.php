<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']" :alternate="$meta['alternate']">
    <x-public-nav :locale="$locale" :alternate-locale="$locale === 'de' ? 'en' : 'de'" :alternate-url="$meta['alternate']" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <nav aria-label="{{ $copy['breadcrumb_label'] }}" class="text-sm text-base-content/65">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route($locale === 'de' ? 'magazine.de.index' : 'magazine.index') }}" class="font-semibold text-primary underline-offset-4 hover:underline">{{ $copy['magazine'] }}</a>
                </li>
                <li aria-hidden="true" class="text-base-content/35">/</li>
                <li class="text-base-content/80">{{ $post['category_label'] }}</li>
                <li aria-hidden="true" class="text-base-content/35">/</li>
                <li aria-current="page" class="max-w-full truncate text-base-content sm:max-w-96">{{ $post['title'] }}</li>
            </ol>
        </nav>

        <article class="mt-8">
            <header class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ $post['category_label'] }}</p>
                <h1 class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-6xl">{{ $post['title'] }}</h1>

                @if ($post['excerpt'])
                    <p class="mt-5 text-xl text-base-content/70">{{ $post['excerpt'] }}</p>
                @endif
            </header>

            <div class="mt-8 overflow-hidden rounded-lg border border-white/10 bg-base-300">
                @if ($post['image'])
                    <img src="{{ $post['image'] }}" alt="{{ $post['image_alt'] ?? $post['title'] }}" class="max-h-[32rem] w-full object-cover">
                @else
                    <x-magazine-placeholder :placeholder="$post['image_placeholder']" class="min-h-96" />
                @endif
            </div>

            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,48rem)_16rem] lg:items-start lg:gap-14">
                <div class="min-w-0">
                    <div class="content-body max-w-none">
                        {!! $post['html'] !!}
                    </div>

                    @foreach ($post['blocks'] as $block)
                        <section class="content-body mt-10 max-w-none">
                            @if ($block['asset'])
                                <img src="{{ $block['asset']['url'] }}" alt="{{ $block['asset']['alt'] ?? '' }}" class="rounded-lg border border-white/10">
                            @endif

                            {!! $block['html'] !!}
                        </section>
                    @endforeach
                </div>

                <aside class="border-t border-white/10 pt-6 text-sm lg:sticky lg:top-8 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6">
                    <p class="font-semibold uppercase tracking-[0.2em] text-primary">{{ $copy['details'] }}</p>

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/45">{{ $copy['category'] }}</dt>
                            <dd class="mt-2 text-base-content/85">{{ $post['category_label'] }}</dd>
                        </div>

                        @if ($meta['alternate'])
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/45">{{ $copy['language'] }}</dt>
                                <dd class="mt-2">
                                    <a href="{{ $meta['alternate'] }}" class="font-semibold text-primary underline-offset-4 hover:underline">{{ $copy['alternate'] }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </aside>
            </div>
        </article>
    </main>
</x-layouts.app>

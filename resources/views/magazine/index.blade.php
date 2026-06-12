<x-layouts.app :title="$meta['title']" :description="$meta['description']">
    <x-public-nav :locale="$locale" :alternate-locale="$alternateLocale" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="mb-12">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-primary">{{ $copy['eyebrow'] }}</p>
            <h1 class="wrap-anywhere mt-4 max-w-4xl text-4xl font-semibold leading-tight text-base-content sm:text-6xl">{{ $copy['heading'] }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-base-content/70">{{ $meta['description'] }}</p>
        </section>

        @if ($posts->isEmpty())
            <div class="rounded-lg border border-white/10 bg-base-200 p-8 text-base-content/70">
                {{ $copy['empty'] }}
            </div>
        @else
            @php($featured = $posts->first())

            <article class="mb-10 grid overflow-hidden rounded-lg border border-white/10 bg-base-200 shadow-2xl shadow-black/20 lg:grid-cols-[1.1fr_0.9fr]">
                <a href="{{ $featured['url'] }}" class="block min-h-80 bg-base-300">
                    @if ($featured['image'])
                        <img src="{{ $featured['image'] }}" alt="{{ $featured['image_alt'] ?? $featured['title'] }}" class="h-full w-full object-cover">
                    @else
                        <x-magazine-placeholder :placeholder="$featured['image_placeholder']" class="h-full min-h-80" />
                    @endif
                </a>

                <div class="flex flex-col justify-center p-6 sm:p-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ $copy['featured'] }}</p>
                    <p class="mt-4 text-sm text-base-content/60">{{ $featured['category_label'] }}</p>
                    <h2 class="wrap-anywhere mt-3 text-3xl font-semibold leading-tight">{{ $featured['title'] }}</h2>

                    @if ($featured['excerpt'])
                        <p class="mt-4 text-base-content/70">{{ $featured['excerpt'] }}</p>
                    @endif

                    <a href="{{ $featured['url'] }}" class="mt-6 inline-flex w-fit rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">
                        {{ $copy['read'] }}
                    </a>
                </div>
            </article>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts->skip(1) as $post)
                    <article class="overflow-hidden rounded-lg border border-white/10 bg-base-200">
                        <a href="{{ $post['url'] }}" class="block aspect-[16/10] bg-base-300">
                            @if ($post['image'])
                                <img src="{{ $post['image'] }}" alt="{{ $post['image_alt'] ?? $post['title'] }}" class="h-full w-full object-cover">
                            @else
                                <x-magazine-placeholder :placeholder="$post['image_placeholder']" class="h-full" />
                            @endif
                        </a>

                        <div class="p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">{{ $post['category_label'] }}</p>
                            <h2 class="wrap-anywhere mt-3 text-xl font-semibold leading-tight">
                                <a href="{{ $post['url'] }}" class="hover:text-primary">{{ $post['title'] }}</a>
                            </h2>

                            @if ($post['excerpt'])
                                <p class="mt-3 line-clamp-3 text-sm text-base-content/70">{{ $post['excerpt'] }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </main>
</x-layouts.app>

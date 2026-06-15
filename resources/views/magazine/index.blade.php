<x-layouts.app :title="$meta['title']" :description="$meta['description']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="sr-only">
            <p
                class="text-primary text-sm font-semibold uppercase tracking-[0.25em]">
                {{ $copy['eyebrow'] }}</p>
            <h1>{{ $copy['heading'] }}</h1>
            <p class="text-base-content/70 mt-5 max-w-2xl text-lg">
                {{ $meta['description'] }}</p>
        </section>

        @if ($posts->isEmpty())
            <div
                class="bg-base-200 text-base-content/70 rounded-lg border border-white/10 p-8">
                {{ $copy['empty'] }}
            </div>
        @else
            @php($featured = $posts->first())

            <a class="border-primary/20 bg-base-200/90 hover:border-primary/40 mb-10 grid overflow-hidden rounded-lg border-2 shadow-2xl shadow-fuchsia-950/25 ring-1 ring-cyan-300/10 transition hover:shadow-xl hover:shadow-fuchsia-950/40 lg:grid-cols-[1.1fr_0.9fr]"
                href="{{ $featured['url'] }}" <article>
                <div class="bg-base-300 block min-h-80"
                    href="{{ $featured['url'] }}">
                    @if ($featured['image'])
                        <img class="h-full w-full object-cover"
                            src="{{ $featured['image'] }}"
                            alt="{{ $featured['image_alt'] ?? $featured['title'] }}">
                    @else
                        <x-magazine-placeholder class="h-full min-h-80"
                            :placeholder="$featured['image_placeholder']" />
                    @endif
                </div>

                <div class="flex flex-col justify-center p-6 sm:p-8">
                    <p class="text-base-content/60 text-sm">
                        {{ $featured['category_label'] }}</p>
                    <h2
                        class="wrap-anywhere mt-3 text-3xl font-semibold leading-tight">
                        {{ $featured['title'] }}</h2>

                    @if ($featured['excerpt'])
                        <p class="text-base-content/70 mt-4">
                            {{ $featured['excerpt'] }}</p>
                    @endif

                    <div
                        class="bg-primary text-primary-content mt-6 inline-flex w-fit rounded-md px-4 py-2 font-semibold transition hover:brightness-110">
                        {{ $copy['read'] }}
                    </div>
                </div>
                </article>
            </a>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts->skip(1) as $post)
                    <a href="{{ $post['url'] }}">
                        <article
                            class="border-primary/15 bg-base-200/90 hover:border-primary/40 overflow-hidden rounded-lg border-2 shadow-lg shadow-fuchsia-950/10 transition hover:shadow-xl hover:shadow-fuchsia-950/40">
                            <div class="aspect-16/10 bg-base-300 block">
                                @if ($post['image'])
                                    <img class="h-full w-full object-cover"
                                        src="{{ $post['image'] }}"
                                        alt="{{ $post['image_alt'] ?? $post['title'] }}">
                                @else
                                    <x-magazine-placeholder class="h-full"
                                        :placeholder="$post['image_placeholder']" />
                                @endif
                                <div>

                                    <div class="p-5">
                                        <p
                                            class="text-primary text-xs font-semibold uppercase tracking-[0.2em]">
                                            {{ $post['category_label'] }}</p>
                                        <h2
                                            class="wrap-anywhere mt-3 text-xl font-semibold leading-tight">
                                            {{ $post['title'] }}
                                        </h2>

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

            <section class="border-primary/20 mt-14 border-t pt-10">
                <p
                    class="text-primary text-sm font-semibold uppercase tracking-[0.25em]">
                    {{ $copy['eyebrow'] }}</p>
                <h2
                    class="wrap-anywhere text-base-content mt-4 text-2xl font-semibold leading-tight sm:text-3xl">
                    {{ $copy['about_heading'] }}</h2>
                <p
                    class="text-base-content/75 mt-4 max-w-3xl text-base leading-7">
                    {{ $copy['about_body'] }}</p>
            </section>
        @endif
    </main>
</x-layouts.app>

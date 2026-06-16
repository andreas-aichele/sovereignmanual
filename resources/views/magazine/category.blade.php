<x-layouts.app :title="$meta['title']" :description="$meta['description']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="max-w-3xl">
            <h1
                class="wrap-anywhere text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $category['title'] }}</h1>

            @if ($category['description'] !== '')
                <p class="mt-5 text-lg leading-8 text-base-content/75">
                    {!! Illuminate\Support\Str::markdown($category['description']) !!}</p>
            @endif
        </header>

        @if ($posts->isEmpty())
            <div
                class="mt-10 rounded-lg border border-white/10 bg-base-200 p-8 text-base-content/70">
                {{ $copy['empty'] }}
            </div>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ $post['url'] }}">
                        <article
                            class="overflow-hidden rounded-lg border-2 border-primary/15 bg-base-200/90 shadow-lg shadow-fuchsia-950/10 transition hover:border-primary/40 hover:shadow-xl hover:shadow-fuchsia-950/40">
                            <div class="aspect-16/10 bg-base-300">
                                <img class="h-full w-full object-cover"
                                    src="{{ $post['image'] }}"
                                    alt="{{ $post['image_alt'] ?? $post['title'] }}">
                            </div>

                            <div class="p-5">
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">
                                    {{ $post['category_label'] }}</p>
                                <h2
                                    class="wrap-anywhere mt-3 text-xl font-semibold leading-tight">
                                    {{ $post['title'] }}</h2>

                                @if ($post['excerpt'])
                                    <p
                                        class="mt-3 line-clamp-3 text-sm text-base-content/70">
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

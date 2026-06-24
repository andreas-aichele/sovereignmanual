<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']"
    :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']" :og-locale="$meta['ogLocale']"
    :og-locale-alternates="$meta['ogLocaleAlternates']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="mb-10 max-w-3xl">
            <p
                class="text-primary text-sm font-semibold uppercase tracking-[0.25em]">
                {{ $copy['eyebrow'] }}</p>
            <h1
                class="wrap-anywhere text-base-content mt-4 text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $copy['heading'] }}</h1>
            <p class="text-base-content/70 mt-5 max-w-2xl text-lg">
                {{ $meta['description'] }}</p>
        </section>

        @if ($posts->isEmpty())
            <div class="alert">
                {{ $copy['empty'] }}
            </div>
        @else
            @php($featured = $posts->first())

            <article>
                <a class="card card-border bg-base-200 lg:card-side mb-10 overflow-hidden shadow-xl transition-shadow hover:shadow-2xl"
                    href="{{ $featured['url'] }}">
                    <figure
                        class="bg-base-300 relative aspect-4/3 min-h-0 w-full lg:aspect-auto lg:min-h-80 lg:w-3/5">
                        <x-img class="h-full w-full object-cover"
                            :src="$featured['image']" :alt="$featured['image_alt'] ??
                                $featured['title']"
                            :responsive="$featured['image_responsive']"
                            sizes="(min-width: 72rem) 40rem, (min-width: 64rem) 60vw, 100vw" hero />

                        <div
                            class="from-base-100 via-base-100/80 absolute inset-x-0 bottom-0 bg-linear-to-t to-transparent p-5 pt-20 text-left lg:hidden">
                            <div class="badge badge-primary mb-3">
                                {{ $featured['category_label'] }}</div>
                            <h2
                                class="wrap-anywhere text-base-content text-2xl font-semibold leading-tight">
                                {{ $featured['title'] }}</h2>
                        </div>
                    </figure>

                    <div class="card-body hidden justify-center lg:flex lg:w-2/5">
                        <div class="badge badge-primary badge-outline">
                            {{ $featured['category_label'] }}</div>
                        <h2
                            class="card-title wrap-anywhere text-3xl leading-tight">
                            {{ $featured['title'] }}</h2>

                        @if ($featured['excerpt'])
                            <p class="text-base-content/70">
                                {{ $featured['excerpt'] }}</p>
                        @endif

                        <div class="card-actions mt-4">
                            <span class="btn btn-primary">
                                {{ $copy['read'] }}
                            </span>
                        </div>
                    </div>
                </a>
            </article>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts->skip(1) as $post)
                    <article>
                        <a class="card card-border card-side bg-base-200 h-full overflow-hidden shadow-sm transition-shadow hover:shadow-lg sm:flex-col"
                            href="{{ $post['url'] }}">
                            <figure
                                class="bg-base-300 aspect-square w-2/5 shrink-0 sm:aspect-16/10 sm:w-full">
                                <x-img class="h-full w-full object-cover"
                                    :src="$post['image']" :alt="$post['image_alt'] ??
                                        $post['title']"
                                    :responsive="$post['image_responsive']"
                                    sizes="(min-width: 64rem) 22rem, (min-width: 40rem) 50vw, 40vw" />
                            </figure>

                            <div class="card-body w-3/5 gap-2 p-4 sm:w-full sm:gap-3 sm:p-6">
                                <div
                                    class="badge badge-primary badge-outline badge-sm">
                                    {{ $post['category_label'] }}</div>
                                <h2
                                    class="card-title wrap-anywhere text-md leading-tight sm:text-xl line-clamp-4 sm:line-clamp-3 text-ellipsis">
                                    {{ $post['title'] }}
                                </h2>

                                @if ($post['excerpt'])
                                    <p
                                        class="text-base-content/70 max-sm:hidden line-clamp-3 text-sm">
                                        {{ $post['excerpt'] }}</p>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>

            <section class="border-base-300 mt-14 border-t pt-10">
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

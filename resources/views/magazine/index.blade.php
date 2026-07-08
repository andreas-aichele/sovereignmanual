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

        @if ($featuredPost === null)
            <div class="alert">
                {{ $copy['empty'] }}
            </div>
        @else
            <article>
                <a class="card card-border bg-base-200 lg:card-side mb-10 overflow-hidden shadow-xl transition-shadow hover:shadow-2xl"
                    href="{{ $featuredPost['url'] }}">
                    <figure
                        class="bg-base-300 relative aspect-4/3 min-h-0 w-full lg:aspect-auto lg:min-h-80 lg:w-3/5">
                        <x-img class="h-full w-full object-cover"
                            :src="$featuredPost['image']" :alt="$featuredPost['image_alt'] ??
                                $featuredPost['title']"
                            :responsive="$featuredPost['image_responsive']"
                            sizes="(min-width: 72rem) 40rem, (min-width: 64rem) 60vw, 100vw" hero />

                        <div
                            class="from-base-100 via-base-100/80 absolute inset-x-0 bottom-0 bg-linear-to-t to-transparent p-5 pt-20 text-left lg:hidden">
                            <div class="badge badge-primary mb-3">
                                {{ $featuredPost['category_label'] }}</div>
                            <h2
                                class="wrap-anywhere text-base-content text-2xl font-semibold leading-tight">
                                {{ $featuredPost['title'] }}</h2>
                        </div>
                    </figure>

                    <div class="card-body hidden justify-center lg:flex lg:w-2/5">
                        <div class="badge badge-primary badge-outline">
                            {{ $featuredPost['category_label'] }}</div>
                        <h2
                            class="card-title wrap-anywhere text-3xl leading-tight">
                            {{ $featuredPost['title'] }}</h2>

                        @if ($featuredPost['excerpt'])
                            <p class="text-base-content/70">
                                {{ $featuredPost['excerpt'] }}</p>
                        @endif

                        <div class="card-actions mt-4">
                            <span class="btn btn-primary">
                                {{ $copy['read'] }}
                            </span>
                        </div>
                    </div>
                </a>
            </article>

            @if ($latestPosts->isNotEmpty())
                <section class="mb-14">
                    <div class="grid gap-6 md:grid-cols-3">
                        @foreach ($latestPosts as $post)
                            @include('magazine.partials.article-card', [
                                'headingLevel' => 2,
                                'post' => $post,
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @foreach ($categorySections as $section)
                <section class="border-base-300 mt-12 border-t pt-10">
                    <div
                        class="mb-5 flex flex-col gap-3">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <h2
                                    class="wrap-anywhere text-base-content text-2xl font-semibold leading-tight">
                                    {{ $section['title'] }}</h2>
                                <a class="btn btn-primary btn-sm shrink-0"
                                    href="{{ $section['url'] }}">
                                    {{ $copy['view_category'] }}
                                </a>
                            </div>
                            @if ($section['description_html'])
                                <div
                                    class="content-body text-base-content/70 mt-2 max-w-2xl text-sm leading-6 [&>p]:my-0 [&>p+p]:mt-2">
                                    {!! $section['description_html'] !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        @foreach ($section['posts'] as $post)
                            @include('magazine.partials.article-card', [
                                'headingLevel' => 3,
                                'post' => $post,
                            ])
                        @endforeach
                    </div>
                </section>
            @endforeach

        @endif
    </main>
</x-layouts.app>

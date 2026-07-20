<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']"
    :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']" :og-locale="$meta['ogLocale']"
    :og-locale-alternates="$meta['ogLocaleAlternates']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="max-w-3xl" aria-labelledby="start-heading">
            <p class="text-primary text-sm font-semibold uppercase tracking-[0.25em]">
                {{ $copy['eyebrow'] }}</p>
            <h1 id="start-heading" class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $copy['heading'] }}</h1>
            <p class="text-base-content/70 mt-5 max-w-2xl text-lg leading-8">
                {{ $copy['intro'] }}</p>
        </section>

        @if (count($pillarSections) > 0)
            <section class="mt-14" aria-labelledby="paths-heading">
                <div class="max-w-2xl">
                    <h2 id="paths-heading" class="wrap-anywhere text-2xl font-semibold leading-tight sm:text-3xl">
                        {{ $copy['paths_heading'] }}</h2>
                    <p class="text-base-content/70 mt-3 leading-7">{{ $copy['paths_intro'] }}</p>
                </div>

                <div class="mt-7 grid gap-5 lg:grid-cols-3">
                    @foreach ($pillarSections as $pillar)
                        <article class="card card-border bg-base-200 h-full shadow-sm">
                            <div class="card-body gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="card-title wrap-anywhere text-xl leading-tight">{{ $pillar['title'] }}</h3>
                                    @if ($pillar['key'] === 'bitcoin-money')
                                        <span class="badge badge-outline bitcoin-pillar-accent shrink-0">Bitcoin</span>
                                    @endif
                                </div>

                                <p class="text-base-content/70 text-sm leading-6">{{ $pillar['description'] }}</p>

                                @if (count($pillar['posts']) > 0)
                                    <ul class="border-base-300 divide-base-300 divide-y border-y">
                                        @foreach ($pillar['posts'] as $post)
                                            <li>
                                                <a class="block py-3 first:pt-3 last:pb-3" href="{{ $post['url'] }}">
                                                    <span class="text-primary text-xs font-semibold uppercase tracking-[0.16em]">
                                                        {{ $post['content_type_label'] }}</span>
                                                    <span class="wrap-anywhere mt-1 block text-sm font-semibold leading-5">
                                                        {{ $post['title'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($pillar['url'])
                                    <div class="card-actions mt-auto pt-1">
                                        <a class="btn btn-primary btn-sm" href="{{ $pillar['url'] }}">
                                            {{ $copy['view_pillar'] }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($featuredPost !== null)
            <section class="border-base-300 mt-16 border-t pt-10" aria-labelledby="featured-heading">
                <div class="mb-6 flex flex-col gap-2">
                    <h2 id="featured-heading" class="wrap-anywhere text-2xl font-semibold leading-tight sm:text-3xl">
                        {{ $copy['featured_heading'] }}</h2>
                </div>

                <article>
                    <a class="card card-border bg-base-200 lg:card-side overflow-hidden shadow-xl transition-shadow hover:shadow-2xl"
                        href="{{ $featuredPost['url'] }}">
                        <figure class="bg-base-300 aspect-4/3 min-h-0 w-full lg:aspect-auto lg:min-h-80 lg:w-3/5">
                            <x-img class="h-full w-full object-cover" :src="$featuredPost['image']"
                                :alt="$featuredPost['image_alt'] ?? $featuredPost['title']"
                                :responsive="$featuredPost['image_responsive']"
                                sizes="(min-width: 72rem) 40rem, (min-width: 64rem) 60vw, 100vw" hero />
                        </figure>

                        <div class="card-body justify-center lg:w-2/5">
                            <div class="flex flex-wrap gap-2">
                                <span class="badge badge-outline badge-sm">{{ $featuredPost['content_type_label'] }}</span>
                                <span class="badge badge-ghost badge-sm">{{ $featuredPost['category_label'] }}</span>
                            </div>
                            <h3 class="card-title wrap-anywhere text-3xl leading-tight">{{ $featuredPost['title'] }}</h3>

                            @if ($featuredPost['excerpt'])
                                <p class="text-base-content/70">{{ $featuredPost['excerpt'] }}</p>
                            @endif

                            <div class="card-actions mt-4">
                                <span class="btn btn-primary">{{ $copy['read'] }}</span>
                            </div>
                        </div>
                    </a>
                </article>
            </section>
        @endif

        @if ($briefings->isNotEmpty())
            <section class="border-base-300 mt-16 border-t pt-10" aria-labelledby="briefing-heading">
                <div class="max-w-2xl">
                    <h2 id="briefing-heading" class="wrap-anywhere text-2xl font-semibold leading-tight sm:text-3xl">
                        {{ $copy['briefing_heading'] }}</h2>
                    <p class="text-base-content/70 mt-3 leading-7">{{ $copy['briefing_intro'] }}</p>
                </div>

                <div class="mt-7 grid gap-6 md:grid-cols-3">
                    @foreach ($briefings as $post)
                        @include('magazine.partials.article-card', ['headingLevel' => 3, 'post' => $post])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($featuredPost === null && $briefings->isEmpty())
            <div class="alert mt-14">{{ $copy['empty'] }}</div>
        @endif

        <section id="newsletter" class="card card-border bg-base-200 mt-16 shadow-sm" aria-labelledby="newsletter-heading">
            <div class="card-body max-w-3xl">
                <h2 id="newsletter-heading" class="card-title wrap-anywhere text-2xl leading-tight sm:text-3xl">
                    {{ $copy['newsletter']['heading'] }}</h2>
                <p class="text-base-content/70 leading-7">{{ $copy['newsletter']['intro'] }}</p>

                @if (session('newsletter_status'))
                    <div class="alert alert-success alert-soft mt-3">{{ session('newsletter_status') }}</div>
                @endif

                <form class="mt-3 grid gap-4" action="{{ route('newsletter.store') }}" method="POST">
                    @csrf
                    <input name="locale" type="hidden" value="{{ $locale }}">

                    <label class="form-control w-full">
                        <span class="label"><span class="label-text">{{ $copy['newsletter']['email'] }}</span></span>
                        <input class="input w-full" name="email" type="email" value="{{ old('email') }}"
                            placeholder="{{ $copy['newsletter']['email_placeholder'] }}" autocomplete="email" required>
                        @error('email')
                            <span class="label"><span class="label-text-alt text-error">{{ $message }}</span></span>
                        @enderror
                    </label>

                    <label class="label cursor-pointer items-start justify-start gap-3">
                        <input class="checkbox checkbox-primary mt-0.5" name="consent" type="checkbox" value="1"
                            @checked(old('consent')) required>
                        <span class="label-text leading-6">{{ $copy['newsletter']['consent'] }}</span>
                    </label>
                    @error('consent')
                        <p class="text-error text-sm">{{ $message }}</p>
                    @enderror

                    <div><button class="btn btn-primary" type="submit">{{ $copy['newsletter']['submit'] }}</button></div>
                </form>
            </div>
        </section>
    </main>
</x-layouts.app>

<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']"
    :alternates="$meta['alternates']" :x-default="$meta['xDefault']" :og-type="$meta['ogType']" :og-locale="$meta['ogLocale']"
    :og-locale-alternates="$meta['ogLocaleAlternates']" :structured-data="$meta['structuredData']">
    <x-public-nav :locale="$locale" :language-options="$languageOptions" />

    <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="border-base-300 max-w-3xl border-b pb-10">
            <p
                class="text-primary text-sm font-semibold uppercase tracking-[0.22em]">
                {{ $copy['eyebrow'] }}</p>
            <h1
                class="wrap-anywhere text-base-content mt-5 text-4xl font-semibold leading-tight sm:text-5xl">
                {{ $copy['heading'] }}</h1>
            <p class="text-base-content/75 mt-6 text-xl leading-9">
                {{ $copy['intro'] }}</p>
        </section>

        <section class="divide-base-300 mt-4 max-w-3xl divide-y">
            @foreach ($copy['sections'] as $section)
                <article class="py-8">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="badge badge-primary shrink-0">
                            {{ $section['badge'] }}
                        </span>
                        <h2
                            class="wrap-anywhere text-base-content text-2xl font-semibold leading-tight">
                            {{ $section['heading'] }}</h2>
                    </div>
                    <p class="text-base-content/75 text-lg leading-8">
                        {{ $section['body'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="border-base-300 max-w-3xl border-t pt-8">
            <h2 class="text-base-content text-2xl font-semibold leading-tight">
                {{ $copy['open_source_heading'] }}</h2>
            <p class="text-base-content/75 mt-4 text-lg leading-8">
                {{ $copy['open_source_body'] }}</p>
            <a class="btn btn-primary mt-6"
                href="https://github.com/andreas-aichele/sovereignmanual"
                rel="noopener noreferrer" target="_blank">
                {{ svg('lucide-github', 'size-4', ['aria-hidden' => 'true']) }}
                {{ $copy['open_source_link'] }}
            </a>
        </section>
    </main>
</x-layouts.app>

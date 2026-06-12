<x-layouts.app :title="$meta['title']" :description="$meta['description']" :canonical="$meta['canonical']" :alternate="$meta['alternate']">
    <x-public-nav :locale="$locale" :alternate-locale="$locale === 'de' ? 'en' : 'de'" :alternate-url="$meta['alternate']" />

    <main class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route($locale === 'de' ? 'magazine.de.index' : 'magazine.index') }}" class="text-sm font-semibold text-primary hover:brightness-110">{{ $copy['back'] }}</a>

        <article class="mt-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ $post['category_label'] }}</p>
            <h1 class="wrap-anywhere mt-4 text-4xl font-semibold leading-tight sm:text-6xl">{{ $post['title'] }}</h1>

            @if ($post['excerpt'])
                <p class="mt-5 text-xl text-base-content/70">{{ $post['excerpt'] }}</p>
            @endif

            <div class="mt-8 overflow-hidden rounded-lg border border-white/10 bg-base-300">
                @if ($post['image'])
                    <img src="{{ $post['image'] }}" alt="{{ $post['image_alt'] ?? $post['title'] }}" class="max-h-[32rem] w-full object-cover">
                @else
                    <x-magazine-placeholder :placeholder="$post['image_placeholder']" class="min-h-96" />
                @endif
            </div>

            <div class="content-body mt-10 max-w-none">
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
        </article>
    </main>
</x-layouts.app>

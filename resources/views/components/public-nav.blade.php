@props(['locale' => 'en', 'languageOptions' => []])

<header class="border-b border-primary/20 bg-base-100/85 shadow-lg shadow-fuchsia-950/20 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route($locale === 'de' ? 'magazine.de.index' : 'magazine.index') }}" class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em] text-primary">
            <img src="/logo.svg" alt="" class="size-8">
            <span>Sovereign Manual</span>
        </a>

        @if (count($languageOptions) > 0)
            <details class="group relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md border border-primary/35 bg-base-200/85 px-3 py-2 text-sm font-semibold text-base-content/85 shadow-sm shadow-fuchsia-950/20 transition hover:border-primary hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <span>{{ strtoupper($locale) }}</span>
                    {{ svg('lucide-chevron-down', 'size-4 text-base-content/45 transition group-open:rotate-180', ['aria-hidden' => 'true']) }}
                    <span class="sr-only">{{ __('magazine.language_switcher', [], $locale) }}</span>
                </summary>

                <div class="absolute right-0 z-20 mt-2 min-w-44 overflow-hidden rounded-lg border border-primary/25 bg-base-200 shadow-2xl shadow-black/40">
                    @foreach ($languageOptions as $option)
                        <a href="{{ $option['url'] }}" @class([
                            'block px-4 py-3 text-sm transition hover:bg-primary/15 hover:text-primary',
                            'font-semibold text-primary' => $option['current'],
                            'text-base-content/80' => ! $option['current'],
                        ]) @if ($option['current']) aria-current="true" @endif>
                            {{ $option['label'] }}
                        </a>
                    @endforeach
                </div>
            </details>
        @endif
    </nav>
</header>

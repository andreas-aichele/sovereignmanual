@props(['locale' => 'en', 'alternateLocale' => null, 'alternateUrl' => null])

<header class="border-b border-white/10 bg-base-100/80 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route($locale === 'de' ? 'magazine.de.index' : 'magazine.index') }}" class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em] text-primary">
            <img src="/logo.svg" alt="" class="size-8">
            <span>Sovereign Manual</span>
        </a>

        @if ($alternateLocale)
            <a href="{{ $alternateUrl ?? route($alternateLocale === 'de' ? 'magazine.de.index' : 'magazine.index') }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-medium text-base-content/80 transition hover:border-primary hover:text-primary">
                {{ strtoupper($alternateLocale) }}
            </a>
        @endif
    </nav>
</header>

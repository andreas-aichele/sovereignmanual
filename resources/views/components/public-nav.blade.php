@props(['locale' => null, 'languageOptions' => []])

@php
    $locale ??= \App\Support\Locales::fallback();
    $categoryNavItems ??= [];
    $homeUrl =
        $locale === \App\Support\Locales::fallback()
            ? route('magazine.index')
            : route('magazine.localized.index', ['locale' => $locale]);
    $aboutUrl =
        $locale === \App\Support\Locales::fallback()
            ? route('magazine.about')
            : route('magazine.localized.about', ['locale' => $locale]);
@endphp

<header
    class="navbar bg-base-100/85 border-base-300 relative z-50 border-b shadow-sm backdrop-blur">
    <nav
        class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a class="text-primary flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
            href="{{ $homeUrl }}">
            <img class="size-8" src="/logo.svg" alt="">
            <span class="hidden sm:inline">Sovereign Manual</span>
        </a>

        <div class="flex items-center gap-2">
            @if (count($categoryNavItems) > 0)
                <details class="dropdown dropdown-end">
                    <summary class="btn btn-ghost btn-sm">
                        {{ __('magazine.nav.categories', [], $locale) }}
                        {{ svg('lucide-chevron-down', 'size-4 text-base-content/45', ['aria-hidden' => 'true']) }}
                    </summary>

                    <ul
                        class="dropdown-content menu bg-base-200 rounded-box border-base-300 z-50 mt-2 w-56 border p-2 shadow-xl">
                        @foreach ($categoryNavItems as $item)
                            <li>
                                <a href="{{ $item['url'] }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

            <a class="btn btn-ghost btn-sm" href="{{ $aboutUrl }}">
                {{ __('magazine.nav.about', [], $locale) }}
            </a>

            @if (count($languageOptions) > 0)
                <details class="dropdown dropdown-end">
                    <summary class="btn btn-outline btn-sm">
                        <span>{{ strtoupper($locale) }}</span>
                        {{ svg('lucide-chevron-down', 'size-4 text-base-content/45', ['aria-hidden' => 'true']) }}
                        <span
                            class="sr-only">{{ __('magazine.language_switcher', [], $locale) }}</span>
                    </summary>

                    <ul
                        class="dropdown-content menu bg-base-200 rounded-box border-base-300 z-50 mt-2 w-44 border p-2 shadow-xl">
                        @foreach ($languageOptions as $option)
                            <li>
                                <a href="{{ $option['url'] }}"
                                    @class(['menu-active' => $option['current']])
                                    @if ($option['current']) aria-current="true" @endif>
                                    {{ $option['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </nav>
</header>

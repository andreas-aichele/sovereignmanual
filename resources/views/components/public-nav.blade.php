@props([
    'locale' => null,
    'languageOptions' => [],
    'pillarNavItems' => [],
    'categoryNavItems' => [],
])

@php
    $locale ??= \App\Support\Locales::fallback();
    $homeUrl =
        $locale === \App\Support\Locales::fallback()
            ? route('magazine.index')
            : route('magazine.localized.index', ['locale' => $locale]);
    $aboutUrl =
        $locale === \App\Support\Locales::fallback()
            ? route('magazine.about')
            : route('magazine.localized.about', ['locale' => $locale]);
    $primaryNavItems =
        count($pillarNavItems) > 0 ? $pillarNavItems : $categoryNavItems;
    $primaryNavLabel =
        count($pillarNavItems) > 0
            ? 'magazine.nav.topics'
            : 'magazine.nav.categories';
@endphp

<header
    class="navbar bg-base-100/85 border-base-300 relative z-50 border-b shadow-sm backdrop-blur">
    <nav
        class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a class="text-primary flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
            href="{{ $homeUrl }}" aria-label="Sovereign Manual">
            <img class="size-8" src="/logo.svg" alt="">
            <span class="hidden sm:inline">Sovereign Manual</span>
        </a>

        <div class="flex items-center gap-2">
            @if (count($primaryNavItems) > 0)
                <details class="dropdown dropdown-end">
                    <summary class="btn btn-ghost btn-sm">
                        {{ __($primaryNavLabel, [], $locale) }}
                        {{ svg('lucide-chevron-down', 'size-4 text-base-content/45', ['aria-hidden' => 'true']) }}
                    </summary>

                    <ul
                        class="dropdown-content menu bg-base-200 rounded-box border-base-300 z-50 mt-2 w-56 border p-2 shadow-xl">
                        @foreach ($primaryNavItems as $item)
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

            <button class="btn btn-ghost btn-square btn-sm"
                data-appearance-toggle type="button"
                title="{{ __('magazine.nav.theme', [], $locale) }}"
                aria-label="{{ __('magazine.nav.theme', [], $locale) }}">
                {{ svg('lucide-sun', 'hidden size-4 dark:block', ['aria-hidden' => 'true']) }}
                {{ svg('lucide-moon', 'size-4 dark:hidden', ['aria-hidden' => 'true']) }}
            </button>

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

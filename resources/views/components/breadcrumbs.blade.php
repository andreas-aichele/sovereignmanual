@props(['label', 'items'])

<nav class="text-base-content/65 text-sm" aria-label="{{ $label }}">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $item)
            <li>
                @if (!empty($item['url']) && !($item['current'] ?? false))
                    <a class="{{ $loop->first ? 'text-primary font-semibold' : 'text-base-content/80 hover:text-primary' }} underline-offset-4 hover:underline"
                        href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span
                        class="text-base-content max-w-full truncate sm:max-w-96"
                        @if ($item['current'] ?? false) aria-current="page" @endif>
                        {{ $item['label'] }}</span>
                @endif
            </li>

            @unless ($loop->last)
                <li class="text-base-content/35" aria-hidden="true">/</li>
            @endunless
        @endforeach
    </ol>
</nav>

<picture class="block h-full w-full">
    @foreach ($sourceSets() as $format => $srcset)
        <source srcset="{{ $srcset }}"
            type="image/{{ $format === 'jpg' ? 'jpeg' : $format }}"
            sizes="{{ $sizes }}">
    @endforeach

    <img
        {{ $attributes->merge(['class' => ''])->merge([
            'src' => $src,
            'alt' => $alt,
            'width' => $width,
            'height' => $height,
            'loading' => $loading(),
            'decoding' => 'async',
            'fetchpriority' => $hero ? 'high' : null,
        ]) }}>
</picture>

<picture>
    @foreach ($sourceSets() as $format => $srcset)
        <source type="image/{{ $format === 'jpg' ? 'jpeg' : $format }}"
            srcset="{{ $srcset }}" sizes="{{ $sizes }}">
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

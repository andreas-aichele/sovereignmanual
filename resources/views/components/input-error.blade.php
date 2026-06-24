@props(['name'])

@error($name)
    <p {{ $attributes->merge(['class' => 'label text-error']) }}>
        {{ $message }}</p>
@enderror

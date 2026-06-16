@props(['name'])

@error($name)
    <p {{ $attributes->merge(['class' => 'mt-2 text-sm text-error']) }}>
        {{ $message }}</p>
@enderror

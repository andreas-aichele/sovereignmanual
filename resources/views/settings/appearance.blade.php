<x-settings-layout title="Appearance">
    <section class="bg-base-200 rounded-lg border border-white/10 p-6">
        <h2 class="text-xl font-semibold">Theme</h2>
        <p class="text-base-content/70 mt-1 text-sm">Choose how the interface
            should render on this device.</p>

        <div class="mt-6 flex flex-wrap gap-3" data-appearance-controls>
            @foreach (['system' => 'System', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                <button data-appearance-value="{{ $value }}" type="button"
                    @class([
                        'rounded-md border px-4 py-2 font-semibold transition',
                        'border-primary bg-primary text-primary-content' =>
                            ($appearance ?? 'system') === $value,
                        'border-white/15 hover:border-primary hover:text-primary' =>
                            ($appearance ?? 'system') !== $value,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>
</x-settings-layout>

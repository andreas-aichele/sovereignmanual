<x-settings-layout title="Appearance">
    <section class="rounded-lg border border-white/10 bg-base-200 p-6">
        <h2 class="text-xl font-semibold">Theme</h2>
        <p class="mt-1 text-sm text-base-content/70">Choose how the interface should render on this device.</p>

        <div class="mt-6 flex flex-wrap gap-3" data-appearance-controls>
            @foreach (['system' => 'System', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                <button type="button" data-appearance-value="{{ $value }}" @class(['rounded-md border px-4 py-2 font-semibold transition', 'border-primary bg-primary text-primary-content' => ($appearance ?? 'system') === $value, 'border-white/15 hover:border-primary hover:text-primary' => ($appearance ?? 'system') !== $value])>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>
</x-settings-layout>

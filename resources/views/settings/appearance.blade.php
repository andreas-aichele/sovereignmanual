<x-settings-layout title="Appearance">
    <section class="card card-border bg-base-200">
        <div class="card-body">
            <h2 class="card-title">Theme</h2>
            <p class="text-base-content/70 text-sm">Choose how the interface
                should render on this device.</p>

            <div class="join mt-4" data-appearance-controls>
                @foreach (['system' => 'System', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                    <button data-appearance-value="{{ $value }}"
                        type="button" @class([
                            'btn join-item',
                            'btn-primary' => ($appearance ?? 'system') === $value,
                            'btn-outline' => ($appearance ?? 'system') !== $value,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>
</x-settings-layout>

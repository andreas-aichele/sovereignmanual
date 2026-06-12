@props(['placeholder'])

<div {{ $attributes->merge(['class' => 'relative flex items-end overflow-hidden bg-base-300 p-6']) }} style="--placeholder-accent: {{ $placeholder['accent'] }}; --placeholder-secondary: {{ $placeholder['secondary'] }};">
    <div class="absolute inset-0 opacity-70" style="background: radial-gradient(circle at 20% 20%, var(--placeholder-accent), transparent 34%), radial-gradient(circle at 80% 10%, var(--placeholder-secondary), transparent 28%), linear-gradient(135deg, #130821, #090312 70%);"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] bg-[size:32px_32px] opacity-25"></div>
    <div class="relative">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/65">{{ $placeholder['category'] }}</p>
        <p class="wrap-anywhere mt-2 text-2xl font-semibold text-white">{{ $placeholder['title'] }}</p>
    </div>
</div>

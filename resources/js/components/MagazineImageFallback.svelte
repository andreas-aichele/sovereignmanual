<script lang="ts">
    type ImagePlaceholder = {
        title: string;
        category: string;
        accent: string;
        secondary: string;
        seed: string;
    };

    let {
        placeholder,
        title,
        class: className = '',
    }: {
        placeholder: ImagePlaceholder;
        title: string;
        class?: string;
    } = $props();

    const seedOffset = $derived(Number(placeholder.seed) % 36);
</script>

<div
    class={`overflow-hidden bg-base-300 ${className}`}
    aria-label={title}
    role="img"
    style={`--accent: ${placeholder.accent}; --secondary: ${placeholder.secondary}; --seed-offset: ${seedOffset}px;`}
>
    <img src="/fallback.jpg" alt="" class="h-full w-full object-cover object-center" />
</div>

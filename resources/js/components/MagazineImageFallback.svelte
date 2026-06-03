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
    class={`relative isolate overflow-hidden bg-base-300 ${className}`}
    aria-label={title}
    role="img"
    style={`--accent: ${placeholder.accent}; --secondary: ${placeholder.secondary}; --seed-offset: ${seedOffset}px;`}
>
    <div
        class="absolute inset-0"
        style="background: radial-gradient(circle at 50% 24%, var(--accent), transparent 28%), linear-gradient(160deg, rgba(8, 5, 24, 0.96), rgba(25, 10, 46, 0.94));"
    ></div>
    <div
        class="absolute top-[18%] left-1/2 h-28 w-56 -translate-x-1/2 rounded-t-full bg-[var(--accent)] opacity-90 blur-[1px] md:h-36 md:w-72"
    ></div>
    <div
        class="absolute inset-x-0 bottom-0 h-1/2"
        style="background: linear-gradient(to bottom, transparent, rgba(5, 4, 18, 0.88)), repeating-linear-gradient(to bottom, transparent 0, transparent 20px, var(--secondary) 21px, var(--secondary) 22px);"
    ></div>
    <div
        class="absolute right-[18%] bottom-[24%] left-[18%] h-1 rounded-full bg-[var(--secondary)]"
        style="box-shadow: 0 0 22px var(--secondary);"
    ></div>
    <div
        class="absolute top-[calc(50%+var(--seed-offset))] left-1/2 flex h-20 w-20 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-4 border-[var(--accent)] bg-base-300/80 text-4xl font-black text-[var(--accent)]"
        style="box-shadow: 0 0 36px var(--accent);"
    >
        ₿
    </div>
</div>

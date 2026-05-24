<script lang="ts">
    let {
        title = 'Sovereign Manual',
        image = null,
        alt = 'Synthwave Bitcoin sovereignty editorial cover',
        compact = false,
    }: {
        title?: string;
        image?: string | null;
        alt?: string | null;
        compact?: boolean;
    } = $props();
</script>

<div
    class={`synthwave-poster relative isolate overflow-hidden border border-neon-cyan/25 bg-void ${compact ? 'aspect-[16/10]' : 'aspect-[16/9]'}`}
>
    {#if image}
        <img
            src={image}
            alt={alt ?? title}
            class="h-full w-full object-cover"
        />
    {:else}
        <div class="absolute inset-0 synthwave-sky"></div>
        <div class="absolute inset-x-0 bottom-0 h-2/3 synthwave-grid"></div>
        <div
            class="absolute left-1/2 top-[43%] h-24 w-24 -translate-x-1/2 rounded-full border border-bitcoin-orange/70 bg-bitcoin-orange/20 shadow-[0_0_70px_rgba(255,153,0,0.65)] md:h-36 md:w-36"
        ></div>
        <div
            class="absolute inset-x-10 top-[56%] h-px bg-neon-pink shadow-[0_0_20px_rgba(255,43,214,0.95)]"
        ></div>
        <div
            class="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4"
        >
            <p
                class="max-w-[18rem] text-sm font-semibold leading-5 tracking-[0.16em] text-white uppercase"
            >
                {title}
            </p>
            <span class="text-xs text-neon-cyan">BTC // 21M</span>
        </div>
    {/if}
</div>

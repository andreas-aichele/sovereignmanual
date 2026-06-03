<script lang="ts">
    type MagazineBlock = {
        id: number;
        type: string;
        markdown: string | null;
        html: string;
        data: Record<string, unknown> | null;
        asset: {
            url: string | null;
            alt: string | null;
        } | null;
    };

    let {
        block,
        fallbackAlt,
    }: {
        block: MagazineBlock;
        fallbackAlt: string;
    } = $props();

    const data = $derived(block.data ?? {});
    const title = $derived(asText(data.title));
    const body = $derived(asText(data.body));
    const caption = $derived(asText(data.caption));
    const items = $derived(asTextArray(data.items));
    const steps = $derived(asTextArray(data.steps));
    const labels = $derived(asTextArray(data.labels));

    function asText(value: unknown): string {
        return typeof value === 'string' ? value : '';
    }

    function asTextArray(value: unknown): string[] {
        return Array.isArray(value)
            ? value.filter((item): item is string => typeof item === 'string')
            : [];
    }
</script>

{#if block.asset?.url}
    <img
        src={block.asset.url}
        alt={block.asset.alt ?? fallbackAlt}
        class="mb-10 w-full rounded-box border border-base-content/15"
    />
{/if}

{#if block.type === 'markdown'}
    <div class="article-markdown">
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        {@html block.html}
    </div>
{:else if block.type === 'insight'}
    <aside
        class="my-10 rounded-box border border-primary/35 bg-base-200/90 p-6 shadow-xl shadow-primary/10"
    >
        {#if title}
            <p class="text-sm font-black tracking-[0.18em] text-primary uppercase">
                {title}
            </p>
        {/if}
        {#if body}
            <p class="mt-3 text-xl font-semibold leading-8 text-base-content">
                {body}
            </p>
        {/if}
    </aside>
{:else if block.type === 'checklist'}
    <section class="my-10 rounded-box border border-secondary/30 bg-base-200/90 p-6">
        {#if title}
            <h2 class="text-2xl font-black text-base-content">{title}</h2>
        {/if}
        <ul class="mt-5 grid gap-3">
            {#each items as item (item)}
                <li class="flex gap-3 rounded-field bg-base-300/70 p-3">
                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-secondary"></span>
                    <span class="leading-7 text-base-content/90">{item}</span>
                </li>
            {/each}
        </ul>
    </section>
{:else if block.type === 'flow_diagram'}
    <section class="my-10 rounded-box border border-accent/35 bg-base-200/90 p-6">
        {#if title}
            <h2 class="text-2xl font-black text-base-content">{title}</h2>
        {/if}
        <div class="mt-6 grid gap-3 md:grid-cols-4">
            {#each steps as step, index (step)}
                <div class="relative rounded-field border border-accent/25 bg-base-300/75 p-4">
                    <span class="badge badge-accent badge-sm mb-3">
                        {index + 1}
                    </span>
                    <p class="font-semibold leading-6 text-base-content">
                        {step}
                    </p>
                </div>
            {/each}
        </div>
    </section>
{:else if block.type === 'sketch'}
    <figure class="my-10 rounded-box border border-primary/30 bg-base-200/90 p-6">
        {#if title}
            <h2 class="text-2xl font-black text-base-content">{title}</h2>
        {/if}
        <div
            class="mt-5 grid min-h-56 place-items-center overflow-hidden rounded-field bg-base-300/80"
        >
            <svg viewBox="0 0 640 260" class="h-full min-h-56 w-full">
                <defs>
                    <linearGradient id={`sketch-line-${block.id}`} x1="0" x2="1">
                        <stop offset="0" stop-color="#F7931A" />
                        <stop offset="1" stop-color="#26D9FF" />
                    </linearGradient>
                </defs>
                <path
                    d="M70 190 C170 70 270 220 380 105 S540 145 585 65"
                    fill="none"
                    stroke={`url(#sketch-line-${block.id})`}
                    stroke-width="8"
                    stroke-linecap="round"
                />
                {#each labels.slice(0, 3) as label, index (label)}
                    <g transform={`translate(${90 + index * 210} ${170 - index * 42})`}>
                        <circle r="18" fill="#F7931A" />
                        <text
                            x="0"
                            y="48"
                            text-anchor="middle"
                            fill="currentColor"
                            class="text-[24px] font-bold text-base-content"
                        >
                            {label}
                        </text>
                    </g>
                {/each}
            </svg>
        </div>
        {#if caption}
            <figcaption class="mt-4 text-sm leading-6 text-base-content/75">
                {caption}
            </figcaption>
        {/if}
    </figure>
{/if}

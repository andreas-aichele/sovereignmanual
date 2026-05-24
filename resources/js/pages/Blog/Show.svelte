<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';

    type BlogBlock = {
        id: number;
        type: string;
        markdown: string | null;
        data: Record<string, unknown> | null;
        asset: {
            url: string | null;
            alt: string | null;
        } | null;
    };

    type BlogPost = {
        title: string;
        excerpt: string | null;
        image: string | null;
        image_alt: string | null;
        audience_level: string;
        published_at: string | null;
        next_review_at: string | null;
        markdown: string;
        blocks: BlogBlock[];
    };

    let {
        locale,
        post,
        meta,
    }: {
        locale: string;
        post: BlogPost;
        meta: {
            title: string;
            description: string | null;
            canonical: string;
            alternate: string | null;
        };
    } = $props();

    const blogUrl = $derived(locale === 'de' ? '/de/blog' : '/blog');

    function sections(markdown: string | null): string[] {
        return (markdown ?? '')
            .split(/\n{2,}/)
            .map((section) => section.trim())
            .filter(Boolean);
    }

    function clean(section: string): string {
        return section.replace(/^#{1,6}\s*/, '').replace(/\*\*/g, '');
    }
</script>

<AppHead title={meta.title}>
    {#if meta.description}
        <meta name="description" content={meta.description} />
    {/if}
    <link rel="canonical" href={meta.canonical} />
    {#if meta.alternate}
        <link
            rel="alternate"
            hreflang={locale === 'de' ? 'en' : 'de'}
            href={meta.alternate}
        />
    {/if}
    <meta property="og:type" content="article" />
    <meta property="og:title" content={meta.title} />
    {#if meta.description}
        <meta property="og:description" content={meta.description} />
    {/if}
</AppHead>

<main class="min-h-screen bg-background text-foreground">
    <header class="border-b border-border">
        <div
            class="mx-auto flex w-full max-w-4xl items-center justify-between gap-6 px-6 py-5"
        >
            <Link
                href={blogUrl}
                class="text-sm font-semibold tracking-wide uppercase"
            >
                Sovereign Manual
            </Link>
            <Link
                href={blogUrl}
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Back to blog
            </Link>
        </div>
    </header>

    <article class="mx-auto w-full max-w-4xl px-6 py-12">
        <div class="flex flex-col gap-5">
            <div
                class="flex flex-wrap items-center gap-3 text-xs font-medium text-muted-foreground uppercase"
            >
                <span>{post.audience_level}</span>
                {#if post.published_at}
                    <time datetime={post.published_at}>
                        {new Date(post.published_at).toLocaleDateString(locale)}
                    </time>
                {/if}
                {#if post.next_review_at}
                    <span>Reviewed yearly</span>
                {/if}
            </div>
            <h1
                class="text-4xl font-semibold tracking-normal text-balance md:text-6xl"
            >
                {post.title}
            </h1>
            {#if post.excerpt}
                <p class="text-xl leading-8 text-muted-foreground">
                    {post.excerpt}
                </p>
            {/if}
        </div>

        {#if post.image}
            <img
                src={post.image}
                alt={post.image_alt ?? post.title}
                class="mt-10 aspect-[16/9] w-full rounded-md border border-border object-cover"
            />
        {/if}

        <div class="mt-12 flex flex-col gap-7 text-lg leading-8">
            {#each post.blocks.length > 0 ? post.blocks : [{ id: 0, type: 'markdown', markdown: post.markdown, data: null, asset: null }] as block (block.id)}
                {#if block.asset?.url}
                    <img
                        src={block.asset.url}
                        alt={block.asset.alt ?? post.title}
                        class="rounded-md border border-border"
                    />
                {/if}
                {#each sections(block.markdown) as section, sectionIndex (sectionIndex)}
                    {#if section.startsWith('#')}
                        <h2 class="pt-4 text-2xl font-semibold leading-9">
                            {clean(section)}
                        </h2>
                    {:else if section.startsWith('- ')}
                        <ul class="list-disc space-y-2 pl-6">
                            {#each section
                                .split('\n')
                                .filter(Boolean) as item, itemIndex (itemIndex)}
                                <li>{clean(item.replace(/^- /, ''))}</li>
                            {/each}
                        </ul>
                    {:else}
                        <p>{clean(section)}</p>
                    {/if}
                {/each}
            {/each}
        </div>
    </article>
</main>

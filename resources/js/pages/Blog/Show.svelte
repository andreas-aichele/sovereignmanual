<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicNav from '@/components/PublicNav.svelte';
    import SynthwavePoster from '@/components/SynthwavePoster.svelte';
    import { index as blogIndex } from '@/routes/blog';
    import { index as germanBlogIndex } from '@/routes/blog/de';

    type BlogBlock = {
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

    type BlogPost = {
        title: string;
        excerpt: string | null;
        image: string | null;
        image_alt: string | null;
        audience_level: string;
        published_at: string | null;
        next_review_at: string | null;
        markdown: string;
        html: string;
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

    const blogUrl = $derived(
        locale === 'de' ? germanBlogIndex.url() : blogIndex.url(),
    );
    const blocks = $derived(
        post.blocks.length > 0
            ? post.blocks
            : [
                  {
                      id: 0,
                      type: 'markdown',
                      markdown: post.markdown,
                      html: post.html,
                      data: null,
                      asset: null,
                  },
              ],
    );
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

<main class="synthwave-page min-h-screen text-white">
    <PublicNav {locale} />

    <article>
        <header class="border-b border-white/10">
            <div
                class="mx-auto grid w-full max-w-7xl gap-10 px-5 py-12 md:px-8 lg:grid-cols-[0.9fr_1.1fr]"
            >
                <div class="flex flex-col justify-end gap-5">
                    <Link
                        href={blogUrl}
                        class="text-sm font-semibold text-neon-cyan hover:text-bitcoin-orange"
                    >
                        Back to archive
                    </Link>
                    <div
                        class="flex flex-wrap items-center gap-3 text-xs font-semibold text-cyan-50/55 uppercase"
                    >
                        <span>{post.audience_level}</span>
                        {#if post.published_at}
                            <time datetime={post.published_at}>
                                {new Date(post.published_at).toLocaleDateString(
                                    locale,
                                )}
                            </time>
                        {/if}
                        {#if post.next_review_at}
                            <span>Freshness tracked</span>
                        {/if}
                    </div>
                    <h1 class="text-4xl font-black leading-tight md:text-6xl">
                        {post.title}
                    </h1>
                    {#if post.excerpt}
                        <p class="text-lg leading-8 text-cyan-50/70">
                            {post.excerpt}
                        </p>
                    {/if}
                </div>

                <SynthwavePoster
                    title={post.title}
                    image={post.image}
                    alt={post.image_alt}
                />
            </div>
        </header>

        <div class="mx-auto w-full max-w-3xl px-5 py-12 md:px-8">
            {#each blocks as block (block.id)}
                {#if block.asset?.url}
                    <img
                        src={block.asset.url}
                        alt={block.asset.alt ?? post.title}
                        class="mb-10 border border-white/10"
                    />
                {/if}
                <div class="article-markdown">
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    {@html block.html}
                </div>
            {/each}
        </div>
    </article>
</main>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicNav from '@/components/PublicNav.svelte';
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
        copy,
        meta,
    }: {
        locale: string;
        post: BlogPost;
        copy: {
            back: string;
            freshness: string;
        };
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

<main class="synthwave-page min-h-screen text-base-content">
    <PublicNav {locale} />

    <article>
        <header class="border-b border-secondary/15">
            <div class="mx-auto w-full max-w-4xl px-5 py-12 md:px-8">
                <div class="flex flex-col gap-5">
                    <Link
                        href={blogUrl}
                        class="btn btn-outline btn-secondary btn-sm w-fit"
                    >
                        {copy.back}
                    </Link>
                    <div
                        class="flex flex-wrap items-center gap-3 text-xs font-semibold text-base-content/55 uppercase"
                    >
                        <span class="badge badge-secondary badge-sm">
                            {post.audience_level}
                        </span>
                        {#if post.published_at}
                            <time datetime={post.published_at}>
                                {new Date(post.published_at).toLocaleDateString(
                                    locale,
                                )}
                            </time>
                        {/if}
                        {#if post.next_review_at}
                            <span>{copy.freshness}</span>
                        {/if}
                    </div>
                    <h1
                        class="text-4xl font-black leading-tight text-base-content md:text-6xl"
                    >
                        {post.title}
                    </h1>
                    {#if post.excerpt}
                        <p class="text-lg leading-8 text-base-content/70">
                            {post.excerpt}
                        </p>
                    {/if}
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-3xl px-5 py-12 md:px-8">
            {#each blocks as block (block.id)}
                {#if block.asset?.url}
                    <img
                        src={block.asset.url}
                        alt={block.asset.alt ?? post.title}
                        class="mb-10 rounded-box border border-secondary/15"
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

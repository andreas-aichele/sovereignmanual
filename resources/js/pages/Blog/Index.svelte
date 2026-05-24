<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicNav from '@/components/PublicNav.svelte';
    import SynthwavePoster from '@/components/SynthwavePoster.svelte';
    import { index as blogIndex } from '@/routes/blog';
    import { index as germanBlogIndex } from '@/routes/blog/de';

    type BlogPost = {
        id: number;
        title: string;
        excerpt: string | null;
        url: string;
        image: string | null;
        image_alt: string | null;
        audience_level: string;
        published_at: string | null;
        next_review_at: string | null;
    };

    type PaginatedPosts = {
        data: BlogPost[];
    };

    let {
        locale,
        posts,
        copy,
        meta,
    }: {
        locale: string;
        alternateLocale: string;
        posts: PaginatedPosts;
        copy: {
            eyebrow: string;
            heading: string;
            featured: string;
            read: string;
            empty: string;
        };
        meta: {
            title: string;
            description: string;
        };
    } = $props();

    const blogUrl = $derived(
        locale === 'de' ? germanBlogIndex.url() : blogIndex.url(),
    );
    const alternateUrl = $derived(
        locale === 'de' ? blogIndex.url() : germanBlogIndex.url(),
    );
    const featuredPost = $derived(posts.data[0] ?? null);
    const latestPosts = $derived(posts.data.slice(1));
</script>

<AppHead title={meta.title}>
    <meta name="description" content={meta.description} />
    <link rel="canonical" href={blogUrl} />
    <link
        rel="alternate"
        hreflang={locale === 'de' ? 'en' : 'de'}
        href={alternateUrl}
    />
</AppHead>

<main class="synthwave-page min-h-screen text-white">
    <PublicNav {locale} />

    <section class="border-b border-white/10">
        <div
            class="mx-auto grid w-full max-w-7xl gap-8 px-5 py-12 md:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-end"
        >
            <div class="flex flex-col gap-5">
                <p
                    class="text-sm font-semibold tracking-[0.24em] text-neon-pink uppercase"
                >
                    {copy.eyebrow}
                </p>
                <h1
                    class="max-w-3xl text-5xl font-black leading-none md:text-7xl"
                >
                    {copy.heading}
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-cyan-50/70">
                    {meta.description}
                </p>
            </div>
            {#if featuredPost}
                <Link href={featuredPost.url} class="group block">
                    <SynthwavePoster
                        title={featuredPost.title}
                        image={featuredPost.image}
                        alt={featuredPost.image_alt}
                    />
                </Link>
            {:else}
                <SynthwavePoster title="Dispatch queue initializing" />
            {/if}
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-5 py-12 md:px-8">
        {#if featuredPost}
            <article
                class="grid gap-6 border-b border-white/10 pb-10 lg:grid-cols-[0.75fr_1.25fr]"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-bitcoin-orange uppercase"
                    >
                        {copy.featured}
                    </p>
                    <p class="mt-3 text-sm text-cyan-50/55">
                        {featuredPost.audience_level}
                    </p>
                </div>
                <div>
                    <h2 class="text-3xl font-black leading-tight md:text-5xl">
                        <Link href={featuredPost.url}>{featuredPost.title}</Link
                        >
                    </h2>
                    {#if featuredPost.excerpt}
                        <p
                            class="mt-5 max-w-3xl text-base leading-7 text-cyan-50/70"
                        >
                            {featuredPost.excerpt}
                        </p>
                    {/if}
                </div>
            </article>
        {/if}

        {#if latestPosts.length > 0}
            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                {#each latestPosts as post (post.id)}
                    <article class="border border-white/10 bg-white/[0.035]">
                        <SynthwavePoster
                            title={post.title}
                            image={post.image}
                            alt={post.image_alt}
                            compact
                        />
                        <div class="flex min-h-64 flex-col gap-4 p-5">
                            <div
                                class="flex items-center justify-between gap-3 text-xs text-cyan-50/55 uppercase"
                            >
                                <span>{post.audience_level}</span>
                                {#if post.published_at}
                                    <time datetime={post.published_at}>
                                        {new Date(
                                            post.published_at,
                                        ).toLocaleDateString(locale)}
                                    </time>
                                {/if}
                            </div>
                            <h2 class="text-xl font-bold leading-7">
                                <Link href={post.url}>{post.title}</Link>
                            </h2>
                            {#if post.excerpt}
                                <p
                                    class="line-clamp-4 text-sm leading-6 text-cyan-50/65"
                                >
                                    {post.excerpt}
                                </p>
                            {/if}
                            <Link
                                href={post.url}
                                class="mt-auto text-sm font-semibold text-neon-cyan hover:text-bitcoin-orange"
                            >
                                {copy.read}
                            </Link>
                        </div>
                    </article>
                {/each}
            </div>
        {:else if !featuredPost}
            <div class="border-y border-white/10 py-16">
                <p class="text-cyan-50/65">{copy.empty}</p>
            </div>
        {/if}
    </section>
</main>

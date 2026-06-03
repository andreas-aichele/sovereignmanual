<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicNav from '@/components/PublicNav.svelte';
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

<main class="synthwave-page min-h-screen text-base-content">
    <PublicNav {locale} />

    <section class="border-b border-secondary/15">
        <div class="mx-auto flex w-full max-w-7xl px-5 py-12 md:px-8">
            <div class="flex max-w-3xl flex-col gap-5">
                <p
                    class="badge badge-primary badge-lg font-semibold tracking-[0.16em] uppercase"
                >
                    {copy.eyebrow}
                </p>
                <h1
                    class="max-w-3xl text-5xl font-black leading-none text-base-content md:text-7xl"
                >
                    {copy.heading}
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-base-content/70">
                    {meta.description}
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-5 py-12 md:px-8">
        {#if featuredPost}
            <article
                class="grid gap-6 border-b border-secondary/15 pb-10 lg:grid-cols-[0.75fr_1.25fr]"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-accent uppercase"
                    >
                        {copy.featured}
                    </p>
                    <p class="mt-3 text-sm text-base-content/55">
                        {featuredPost.audience_level}
                    </p>
                </div>
                <div>
                    <h2
                        class="text-3xl font-black leading-tight text-base-content md:text-5xl"
                    >
                        <Link href={featuredPost.url}>{featuredPost.title}</Link
                        >
                    </h2>
                    {#if featuredPost.excerpt}
                        <p
                            class="mt-5 max-w-3xl text-base leading-7 text-base-content/70"
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
                    <article
                        class="card overflow-hidden border border-secondary/15 bg-base-200/80 shadow-xl shadow-primary/5"
                    >
                        <div class="flex min-h-64 flex-col gap-4 p-5">
                            <div
                                class="flex items-center justify-between gap-3 text-xs text-base-content/55 uppercase"
                            >
                                <span class="badge badge-secondary badge-sm">
                                    {post.audience_level}
                                </span>
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
                                    class="line-clamp-4 text-sm leading-6 text-base-content/65"
                                >
                                    {post.excerpt}
                                </p>
                            {/if}
                            <Link
                                href={post.url}
                                class="btn btn-outline btn-secondary btn-sm mt-auto w-fit"
                            >
                                {copy.read}
                            </Link>
                        </div>
                    </article>
                {/each}
            </div>
        {:else if !featuredPost}
            <div class="alert border-secondary/15 bg-base-200 py-6">
                <p class="text-base-content/65">{copy.empty}</p>
            </div>
        {/if}
    </section>
</main>

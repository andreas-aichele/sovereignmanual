<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PublicNav from '@/components/PublicNav.svelte';
    import { index as magazineIndex } from '@/routes/magazine';
    import { index as germanMagazineIndex } from '@/routes/magazine/de';

    type MagazinePost = {
        id: number;
        title: string;
        excerpt: string | null;
        url: string;
        image: string | null;
        image_alt: string | null;
        audience_level: string;
        category: string;
        category_label: string;
        published_at: string | null;
        next_review_at: string | null;
    };

    type PaginatedPosts = {
        data: MagazinePost[];
    };

    type CategorySection = {
        key: string;
        label: string;
        posts: MagazinePost[];
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

    const magazineUrl = $derived(
        locale === 'de' ? germanMagazineIndex.url() : magazineIndex.url(),
    );
    const alternateUrl = $derived(
        locale === 'de' ? magazineIndex.url() : germanMagazineIndex.url(),
    );
    const featuredPost = $derived(posts.data[0] ?? null);
    const latestPosts = $derived(posts.data.slice(1));
    const categorySections = $derived(groupPostsByCategory(latestPosts));

    function groupPostsByCategory(posts: MagazinePost[]): CategorySection[] {
        const sections = new Map<string, CategorySection>();

        for (const post of posts) {
            if (!sections.has(post.category)) {
                sections.set(post.category, {
                    key: post.category,
                    label: post.category_label,
                    posts: [],
                });
            }

            sections.get(post.category)?.posts.push(post);
        }

        return Array.from(sections.values());
    }

    function formatDate(date: string | null): string | null {
        if (!date) {
            return null;
        }

        return new Date(date).toLocaleDateString(locale);
    }
</script>

<AppHead title={meta.title}>
    <meta name="description" content={meta.description} />
    <link rel="canonical" href={magazineUrl} />
    <link
        rel="alternate"
        hreflang={locale === 'de' ? 'en' : 'de'}
        href={alternateUrl}
    />
</AppHead>

<main class="synthwave-page min-h-screen text-base-content">
    <PublicNav {locale} />

    <section class="mx-auto w-full max-w-7xl px-5 py-10 md:px-8 md:py-14">
        {#if featuredPost}
            <article
                class={`grid overflow-hidden rounded-box border border-primary/35 bg-base-200/90 shadow-2xl shadow-primary/10 ${featuredPost.image ? 'lg:grid-cols-[1.2fr_0.8fr]' : ''}`}
            >
                <div
                    class="flex min-h-[28rem] flex-col justify-end gap-6 p-6 md:p-10"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="badge badge-primary badge-lg">
                            {copy.featured}
                        </span>
                        <span class="badge badge-secondary badge-lg">
                            {featuredPost.category_label}
                        </span>
                    </div>
                    <h2
                        class="max-w-4xl text-4xl font-black leading-tight text-base-content md:text-7xl"
                    >
                        <Link href={featuredPost.url}>{featuredPost.title}</Link
                        >
                    </h2>
                    {#if featuredPost.excerpt}
                        <p
                            class="max-w-3xl text-lg leading-8 text-base-content/85"
                        >
                            {featuredPost.excerpt}
                        </p>
                    {/if}
                    <div
                        class="flex flex-wrap items-center gap-4 text-sm text-base-content/75"
                    >
                        <span class="font-semibold uppercase">
                            {featuredPost.audience_level}
                        </span>
                        {#if formatDate(featuredPost.published_at)}
                            <time datetime={featuredPost.published_at}>
                                {formatDate(featuredPost.published_at)}
                            </time>
                        {/if}
                        <Link
                            href={featuredPost.url}
                            class="btn btn-primary btn-sm"
                        >
                            {copy.read}
                        </Link>
                    </div>
                </div>
                {#if featuredPost.image}
                    <img
                        src={featuredPost.image}
                        alt={featuredPost.image_alt ?? featuredPost.title}
                        class="h-full min-h-80 w-full object-cover"
                    />
                {/if}
            </article>
        {:else}
            <div class="alert border-base-content/15 bg-base-200 py-6">
                <p class="text-base-content/80">{copy.empty}</p>
            </div>
        {/if}

        {#if categorySections.length > 0}
            <div class="mt-14 flex flex-col gap-14">
                {#each categorySections as section (section.key)}
                    <section>
                        <div
                            class="mb-5 flex items-end justify-between gap-4 border-b border-base-content/15 pb-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.22em] text-primary uppercase"
                                >
                                    {copy.eyebrow}
                                </p>
                                <h2
                                    class="mt-2 text-3xl font-black text-base-content"
                                >
                                    {section.label}
                                </h2>
                            </div>
                            <span class="badge badge-outline">
                                {section.posts.length}
                            </span>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            {#each section.posts as post (post.id)}
                                <article
                                    class="card overflow-hidden border border-base-content/15 bg-base-200/85 shadow-xl shadow-primary/5"
                                >
                                    {#if post.image}
                                        <img
                                            src={post.image}
                                            alt={post.image_alt ?? post.title}
                                            class="aspect-[16/9] w-full object-cover"
                                        />
                                    {/if}
                                    <div
                                        class="flex min-h-64 flex-col gap-4 p-5"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3 text-xs text-base-content/75 uppercase"
                                        >
                                            <span
                                                class="badge badge-secondary badge-sm"
                                            >
                                                {post.audience_level}
                                            </span>
                                            {#if formatDate(post.published_at)}
                                                <time
                                                    datetime={post.published_at}
                                                >
                                                    {formatDate(
                                                        post.published_at,
                                                    )}
                                                </time>
                                            {/if}
                                        </div>
                                        <h3 class="text-xl font-bold leading-7">
                                            <Link href={post.url}>
                                                {post.title}
                                            </Link>
                                        </h3>
                                        {#if post.excerpt}
                                            <p
                                                class="line-clamp-4 text-sm leading-6 text-base-content/80"
                                            >
                                                {post.excerpt}
                                            </p>
                                        {/if}
                                        <Link
                                            href={post.url}
                                            class="btn btn-outline btn-primary btn-sm mt-auto w-fit"
                                        >
                                            {copy.read}
                                        </Link>
                                    </div>
                                </article>
                            {/each}
                        </div>
                    </section>
                {/each}
            </div>
        {/if}
    </section>
</main>

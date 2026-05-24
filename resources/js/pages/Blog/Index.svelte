<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';

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
        meta,
    }: {
        locale: string;
        alternateLocale: string;
        posts: PaginatedPosts;
        meta: {
            title: string;
            description: string;
        };
    } = $props();

    const blogUrl = $derived(locale === 'de' ? '/de/blog' : '/blog');
    const alternateUrl = $derived(locale === 'de' ? '/blog' : '/de/blog');
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

<main class="min-h-screen bg-background text-foreground">
    <header class="border-b border-border">
        <div
            class="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-6 py-5"
        >
            <Link
                href={blogUrl}
                class="text-sm font-semibold tracking-wide uppercase"
            >
                Sovereign Manual
            </Link>
            <nav class="flex items-center gap-4 text-sm text-muted-foreground">
                <Link href={alternateUrl} class="hover:text-foreground">
                    {locale === 'de' ? 'English' : 'Deutsch'}
                </Link>
                <Link href="/login" class="hover:text-foreground">Admin</Link>
            </nav>
        </div>
    </header>

    <section class="border-b border-border">
        <div
            class="mx-auto grid w-full max-w-6xl gap-8 px-6 py-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-end"
        >
            <div class="flex flex-col gap-5">
                <p class="text-sm font-medium text-muted-foreground">
                    Bitcoin, sovereignty, and financial intelligence
                </p>
                <h1
                    class="max-w-3xl text-4xl font-semibold tracking-normal text-balance md:text-6xl"
                >
                    Sovereign Manual
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-muted-foreground">
                    {meta.description}
                </p>
            </div>
            <div
                class="aspect-[4/3] overflow-hidden rounded-md border border-border bg-muted"
            >
                <img
                    src="https://images.unsplash.com/photo-1518546305927-5a555bb7020d"
                    alt="Bitcoin market and sovereignty visual"
                    class="h-full w-full object-cover"
                />
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-6 py-12">
        {#if posts.data.length > 0}
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                {#each posts.data as post (post.id)}
                    <article
                        class="flex min-h-[28rem] flex-col overflow-hidden rounded-md border border-border bg-card"
                    >
                        {#if post.image}
                            <img
                                src={post.image}
                                alt={post.image_alt ?? post.title}
                                class="aspect-[16/10] w-full object-cover"
                            />
                        {/if}
                        <div class="flex flex-1 flex-col gap-4 p-5">
                            <div
                                class="flex items-center justify-between gap-3 text-xs text-muted-foreground uppercase"
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
                            <h2 class="text-xl font-semibold leading-7">
                                <Link href={post.url} class="hover:underline"
                                    >{post.title}</Link
                                >
                            </h2>
                            {#if post.excerpt}
                                <p
                                    class="line-clamp-4 text-sm leading-6 text-muted-foreground"
                                >
                                    {post.excerpt}
                                </p>
                            {/if}
                            <Link
                                href={post.url}
                                class="mt-auto text-sm font-medium hover:underline"
                            >
                                Read article
                            </Link>
                        </div>
                    </article>
                {/each}
            </div>
        {:else}
            <div class="border-y border-border py-16">
                <p class="text-muted-foreground">No published articles yet.</p>
            </div>
        {/if}
    </section>
</main>

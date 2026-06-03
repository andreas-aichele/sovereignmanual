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
    };

    let {
        locale,
        featuredPost,
        latestPosts,
        copy,
        meta,
    }: {
        locale: string;
        featuredPost: BlogPost | null;
        latestPosts: BlogPost[];
        copy: {
            eyebrow: string;
            intro: string;
            primaryCta: string;
            secondaryCta: string;
            signalEyebrow: string;
            signalTitle: string;
            topics: {
                title: string;
                body: string;
            }[];
        };
        meta: {
            title: string;
            description: string;
        };
    } = $props();

    const blogUrl = $derived(
        locale === 'de' ? germanBlogIndex.url() : blogIndex.url(),
    );
</script>

<AppHead title={meta.title}>
    <meta name="description" content={meta.description} />
</AppHead>

<main class="synthwave-page min-h-screen text-base-content">
    <PublicNav {locale} />

    <section class="relative overflow-hidden border-b border-secondary/15">
        <div class="absolute inset-0 synthwave-hero-grid"></div>
        <div
            class="relative mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-7xl items-center px-5 py-14 md:px-8"
        >
            <div class="flex max-w-4xl flex-col gap-7">
                <p
                    class="badge badge-secondary badge-lg font-semibold tracking-[0.18em] uppercase"
                >
                    {copy.eyebrow}
                </p>
                <h1
                    class="max-w-4xl text-5xl font-black leading-[0.95] text-base-content md:text-7xl"
                >
                    Sovereign Manual
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-base-content/75">
                    {copy.intro}
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link href={blogUrl} class="btn btn-primary">
                        {copy.primaryCta}
                    </Link>
                    {#if featuredPost}
                        <Link
                            href={featuredPost.url}
                            class="btn btn-outline btn-secondary"
                        >
                            {copy.secondaryCta}
                        </Link>
                    {/if}
                </div>
            </div>
        </div>
    </section>

    <section
        class="mx-auto grid w-full max-w-7xl gap-8 px-5 py-12 md:px-8 lg:grid-cols-[0.9fr_1.1fr]"
    >
        <div>
            <p
                class="text-sm font-semibold tracking-[0.22em] text-accent uppercase"
            >
                {copy.signalEyebrow}
            </p>
            <h2 class="mt-3 text-3xl font-black text-base-content">
                {copy.signalTitle}
            </h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            {#each copy.topics as topic (topic.title)}
                <div
                    class="card border border-secondary/15 bg-base-200/70 shadow-xl shadow-primary/5"
                >
                    <div class="card-body gap-3 p-5">
                        <p class="text-sm font-semibold text-secondary">
                            {topic.title}
                        </p>
                        <p class="text-sm leading-6 text-base-content/65">
                            {topic.body}
                        </p>
                    </div>
                </div>
            {/each}
        </div>
    </section>

    {#if latestPosts.length > 0}
        <section class="mx-auto w-full max-w-7xl px-5 pb-16 md:px-8">
            <div class="grid gap-5 md:grid-cols-3">
                {#each latestPosts as post (post.id)}
                    <article
                        class="card overflow-hidden border border-secondary/15 bg-base-200/80 shadow-xl shadow-primary/5"
                    >
                        <div class="card-body p-5">
                            <p class="badge badge-primary badge-sm">
                                {post.audience_level}
                            </p>
                            <h3 class="mt-3 text-xl font-bold leading-7">
                                <Link href={post.url}>{post.title}</Link>
                            </h3>
                        </div>
                    </article>
                {/each}
            </div>
        </section>
    {/if}
</main>

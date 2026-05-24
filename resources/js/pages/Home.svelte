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

<main class="synthwave-page min-h-screen text-white">
    <PublicNav {locale} />

    <section class="relative overflow-hidden border-b border-white/10">
        <div class="absolute inset-0 synthwave-hero-grid"></div>
        <div
            class="relative mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-7xl items-center gap-10 px-5 py-16 md:px-8 lg:grid-cols-[1fr_0.92fr]"
        >
            <div class="flex flex-col gap-7">
                <p
                    class="text-sm font-semibold tracking-[0.28em] text-neon-cyan uppercase"
                >
                    {copy.eyebrow}
                </p>
                <h1
                    class="max-w-4xl text-5xl font-black leading-[0.95] md:text-7xl"
                >
                    Sovereign Manual
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-cyan-50/75">
                    {copy.intro}
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        href={blogUrl}
                        class="border border-neon-cyan bg-neon-cyan px-5 py-3 text-sm font-semibold text-void shadow-[0_0_24px_rgba(35,240,255,0.35)]"
                    >
                        {copy.primaryCta}
                    </Link>
                    {#if featuredPost}
                        <Link
                            href={featuredPost.url}
                            class="border border-white/20 px-5 py-3 text-sm font-semibold text-cyan-50 hover:border-bitcoin-orange hover:text-bitcoin-orange"
                        >
                            {copy.secondaryCta}
                        </Link>
                    {/if}
                </div>
            </div>

            <SynthwavePoster
                title={featuredPost?.title ??
                    'Bitcoin sovereignty operating manual'}
                image={featuredPost?.image ?? null}
                alt={featuredPost?.image_alt ?? null}
            />
        </div>
    </section>

    <section
        class="mx-auto grid w-full max-w-7xl gap-8 px-5 py-12 md:px-8 lg:grid-cols-[0.9fr_1.1fr]"
    >
        <div>
            <p
                class="text-sm font-semibold tracking-[0.22em] text-bitcoin-orange uppercase"
            >
                {copy.signalEyebrow}
            </p>
            <h2 class="mt-3 text-3xl font-black">
                {copy.signalTitle}
            </h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            {#each copy.topics as topic (topic.title)}
                <div
                    class="pixel-panel border border-white/10 bg-white/[0.03] p-5"
                >
                    <p class="text-sm font-semibold text-neon-cyan">
                        {topic.title}
                    </p>
                    <p class="mt-3 text-sm leading-6 text-cyan-50/65">
                        {topic.body}
                    </p>
                </div>
            {/each}
        </div>
    </section>

    {#if latestPosts.length > 0}
        <section class="mx-auto w-full max-w-7xl px-5 pb-16 md:px-8">
            <div class="grid gap-5 md:grid-cols-3">
                {#each latestPosts as post (post.id)}
                    <article class="border border-white/10 bg-white/[0.035]">
                        <SynthwavePoster
                            title={post.title}
                            image={post.image}
                            alt={post.image_alt}
                            compact
                        />
                        <div class="p-5">
                            <p
                                class="text-xs tracking-[0.2em] text-neon-pink uppercase"
                            >
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

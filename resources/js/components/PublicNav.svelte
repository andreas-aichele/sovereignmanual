<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { home } from '@/routes';
    import { index as blogIndex } from '@/routes/blog';
    import { index as germanBlogIndex } from '@/routes/blog/de';
    import { de as germanHome } from '@/routes/home';

    let { locale = 'en' }: { locale?: string } = $props();

    const homeUrl = $derived(locale === 'de' ? germanHome.url() : home.url());
    const blogUrl = $derived(
        locale === 'de' ? germanBlogIndex.url() : blogIndex.url(),
    );
    const alternateUrl = $derived(
        locale === 'de' ? blogIndex.url() : germanBlogIndex.url(),
    );
</script>

<header
    class="sticky top-0 z-50 border-b border-white/10 bg-void/85 backdrop-blur-xl"
>
    <div
        class="mx-auto flex w-full max-w-7xl items-center justify-between gap-6 px-5 py-4 md:px-8"
    >
        <Link
            href={homeUrl}
            class="flex items-center gap-3 text-sm font-semibold"
        >
            <span
                class="h-3 w-3 border border-neon-cyan bg-bitcoin-orange shadow-[0_0_18px_rgba(255,153,0,0.85)]"
            ></span>
            <span class="tracking-[0.22em] text-white uppercase">
                Sovereign Manual
            </span>
        </Link>

        <nav class="flex items-center gap-5 text-sm text-cyan-100/75">
            <Link href={homeUrl} class="hover:text-neon-cyan">
                {locale === 'de' ? 'Start' : 'Home'}
            </Link>
            <Link href={blogUrl} class="hover:text-neon-cyan">
                {locale === 'de' ? 'Blog' : 'Blog'}
            </Link>
            <Link href={alternateUrl} class="hover:text-bitcoin-orange">
                {locale === 'de' ? 'English' : 'Deutsch'}
            </Link>
        </nav>
    </div>
</header>

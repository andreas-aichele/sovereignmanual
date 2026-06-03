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
    class="sticky top-0 z-50 border-b border-secondary/15 bg-base-100/82 backdrop-blur-xl"
>
    <div class="navbar mx-auto min-h-16 w-full max-w-7xl px-5 md:px-8">
        <Link
            href={homeUrl}
            class="flex min-w-0 flex-1 items-center gap-3 text-sm font-semibold"
        >
            <img src="/logo.svg" alt="" class="size-8 shrink-0" />
            <span
                class="truncate tracking-[0.18em] text-base-content uppercase"
            >
                Sovereign Manual
            </span>
        </Link>

        <nav class="flex items-center gap-1 text-sm">
            <Link href={homeUrl} class="btn btn-ghost btn-sm">
                {locale === 'de' ? 'Start' : 'Home'}
            </Link>
            <Link href={blogUrl} class="btn btn-ghost btn-sm">
                {locale === 'de' ? 'Blog' : 'Blog'}
            </Link>
            <Link
                href={alternateUrl}
                class="btn btn-outline btn-secondary btn-sm"
            >
                {locale === 'de' ? 'English' : 'Deutsch'}
            </Link>
        </nav>
    </div>
</header>

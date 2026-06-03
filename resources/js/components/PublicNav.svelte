<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { index as magazineIndex } from '@/routes/magazine';
    import { index as germanMagazineIndex } from '@/routes/magazine/de';

    let { locale = 'en' }: { locale?: string } = $props();

    const magazineUrl = $derived(
        locale === 'de' ? germanMagazineIndex.url() : magazineIndex.url(),
    );
    const alternateUrl = $derived(
        locale === 'de' ? magazineIndex.url() : germanMagazineIndex.url(),
    );
</script>

<header
    class="sticky top-0 z-50 border-b border-primary/35 bg-base-100/95 shadow-lg shadow-primary/10 backdrop-blur-xl"
>
    <div class="navbar mx-auto min-h-20 w-full max-w-7xl px-5 md:px-8">
        <Link
            href={magazineUrl}
            class="flex min-w-0 flex-1 items-center gap-4 font-semibold"
        >
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-box border border-primary/35 bg-base-200 shadow-md shadow-primary/20"
            >
                <img src="/logo.svg" alt="" class="size-8" />
            </span>
            <span class="flex min-w-0 flex-col">
                <span
                    class="truncate text-base tracking-[0.16em] text-base-content uppercase md:text-lg"
                >
                    Sovereign Manual
                </span>
                <span
                    class="hidden text-xs font-medium tracking-[0.22em] text-primary uppercase sm:block"
                >
                    Bitcoin sovereignty
                </span>
            </span>
        </Link>

        <nav class="flex items-center gap-2 text-sm">
            <Link
                href={alternateUrl}
                class="btn btn-outline btn-primary btn-sm"
            >
                {locale === 'de' ? 'English' : 'Deutsch'}
            </Link>
        </nav>
    </div>
</header>

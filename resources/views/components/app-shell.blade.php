<div class="bg-base-100 min-h-screen">
    <header class="bg-base-200 border-b border-white/10">
        <nav
            class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a class="text-primary flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
                href="{{ route('dashboard') }}">
                <img class="size-8" src="/logo.svg" alt="">
                <span>Sovereign Manual</span>
            </a>

            <div class="flex items-center gap-3 text-sm">
                <a class="text-base-content/70 hover:text-primary"
                    href="{{ route('magazine.index') }}">Magazine</a>
                <a class="text-base-content/70 hover:text-primary"
                    href="{{ route('profile.edit') }}">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        class="hover:border-primary hover:text-primary rounded-md border border-white/15 px-3 py-2 font-medium"
                        type="submit">Log out</button>
                </form>
            </div>
        </nav>
    </header>

    {{ $slot }}
</div>

<div class="min-h-screen bg-base-100">
    <header class="border-b border-white/10 bg-base-200">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em] text-primary">
                <img src="/logo.svg" alt="" class="size-8">
                <span>Sovereign Manual</span>
            </a>

            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('magazine.index') }}" class="text-base-content/70 hover:text-primary">Magazine</a>
                <a href="{{ route('profile.edit') }}" class="text-base-content/70 hover:text-primary">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-white/15 px-3 py-2 font-medium hover:border-primary hover:text-primary">Log out</button>
                </form>
            </div>
        </nav>
    </header>

    {{ $slot }}
</div>

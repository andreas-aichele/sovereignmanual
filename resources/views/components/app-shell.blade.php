<div class="bg-base-100 min-h-screen">
    <header class="navbar bg-base-200 border-base-300 border-b">
        <nav
            class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a class="text-primary flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
                href="{{ route('dashboard') }}">
                <img class="size-8" src="/logo.svg" alt="">
                <span>Sovereign Manual</span>
            </a>

            <ul class="menu menu-horizontal items-center gap-1">
                <li><a href="{{ route('magazine.index') }}">Magazine</a></li>
                <li><a href="{{ route('profile.edit') }}">Settings</a></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline btn-sm"
                            type="submit">Log out</button>
                    </form>
                </li>
            </ul>
        </nav>
    </header>

    {{ $slot }}
</div>

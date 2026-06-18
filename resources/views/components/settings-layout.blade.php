@props(['title'])

<x-layouts.app :title="$title">
    <x-app-shell>
        <div
            class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
            <aside>
                <h1 class="mb-4 text-2xl font-semibold">{{ $title }}</h1>
                <ul class="menu bg-base-200 rounded-box w-full">
                    <li>
                        <a href="{{ route('profile.edit') }}"
                            @class(['menu-active' => request()->routeIs('profile.edit')])>Profile</a>
                    </li>
                    <li>
                        <a href="{{ route('security.edit') }}"
                            @class(['menu-active' => request()->routeIs('security.edit')])>Security</a>
                    </li>
                    <li>
                        <a href="{{ route('appearance.edit') }}"
                            @class(['menu-active' => request()->routeIs('appearance.edit')])>Appearance</a>
                    </li>
                </ul>
            </aside>

            <section class="min-w-0">
                {{ $slot }}
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>

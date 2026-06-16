@props(['title'])

<x-layouts.app :title="$title">
    <x-app-shell>
        <div
            class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
            <aside class="space-y-2">
                <h1 class="mb-4 text-2xl font-semibold">{{ $title }}</h1>
                <a href="{{ route('profile.edit') }}"
                    @class([
                        'block rounded-md px-3 py-2 text-sm font-medium',
                        'bg-primary text-primary-content' => request()->routeIs('profile.edit'),
                        'text-base-content/75 hover:bg-base-300 hover:text-base-content' => !request()->routeIs(
                            'profile.edit'),
                    ])>Profile</a>
                <a href="{{ route('security.edit') }}"
                    @class([
                        'block rounded-md px-3 py-2 text-sm font-medium',
                        'bg-primary text-primary-content' => request()->routeIs('security.edit'),
                        'text-base-content/75 hover:bg-base-300 hover:text-base-content' => !request()->routeIs(
                            'security.edit'),
                    ])>Security</a>
                <a href="{{ route('appearance.edit') }}"
                    @class([
                        'block rounded-md px-3 py-2 text-sm font-medium',
                        'bg-primary text-primary-content' => request()->routeIs('appearance.edit'),
                        'text-base-content/75 hover:bg-base-300 hover:text-base-content' => !request()->routeIs(
                            'appearance.edit'),
                    ])>Appearance</a>
            </aside>

            <section class="min-w-0">
                {{ $slot }}
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>

@props(['title', 'description' => null])

<x-layouts.app :title="$title" :description="$description">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section
            class="bg-base-200 w-full max-w-md rounded-lg border border-white/10 p-6 shadow-2xl shadow-black/30 sm:p-8">
            <a class="text-primary mb-8 flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
                href="{{ route('magazine.index') }}">
                <img class="size-9" src="/logo.svg" alt="">
                <span>Sovereign Manual</span>
            </a>

            <div class="mb-6">
                <h1 class="text-base-content text-2xl font-semibold">
                    {{ $title }}</h1>

                @if ($description)
                    <p class="text-base-content/70 mt-2 text-sm">
                        {{ $description }}</p>
                @endif
            </div>

            @if (session('status'))
                <div
                    class="border-success/30 bg-success/10 text-success mb-4 rounded-md border p-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </section>
    </main>
</x-layouts.app>

@props(['title', 'description' => null])

<x-layouts.app :title="$title" :description="$description">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="w-full max-w-md rounded-lg border border-white/10 bg-base-200 p-6 shadow-2xl shadow-black/30 sm:p-8">
            <a href="{{ route('magazine.index') }}" class="mb-8 flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em] text-primary">
                <img src="/logo.svg" alt="" class="size-9">
                <span>Sovereign Manual</span>
            </a>

            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-base-content">{{ $title }}</h1>

                @if ($description)
                    <p class="mt-2 text-sm text-base-content/70">{{ $description }}</p>
                @endif
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md border border-success/30 bg-success/10 p-3 text-sm text-success">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </section>
    </main>
</x-layouts.app>

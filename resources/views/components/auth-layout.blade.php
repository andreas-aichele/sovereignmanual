@props(['title', 'description' => null])

<x-layouts.app :title="$title" :description="$description">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="card card-border bg-base-200 w-full max-w-md shadow-xl">
            <div class="card-body">
                <a class="text-primary mb-4 flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em]"
                    href="{{ route('magazine.index') }}">
                    <img class="size-9" src="/logo.svg" alt="">
                    <span>Sovereign Manual</span>
                </a>

                <div class="mb-2">
                    <h1 class="card-title text-2xl">
                        {{ $title }}</h1>

                    @if ($description)
                        <p class="text-base-content/70 mt-2 text-sm">
                            {{ $description }}</p>
                    @endif
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-soft text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </section>
    </main>
</x-layouts.app>

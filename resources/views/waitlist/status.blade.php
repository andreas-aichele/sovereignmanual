<x-layouts.app :title="$title" robots="noindex, nofollow">
    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-4 py-10 sm:px-6">
        <section class="card card-border bg-base-200 w-full shadow-sm">
            <div class="card-body gap-4 p-8 sm:p-10">
                <h1 class="card-title text-2xl leading-tight sm:text-3xl">{{ $title }}</h1>
                <p class="text-base-content/75 text-lg leading-8">{{ $message }}</p>
                <a class="btn btn-primary mt-2 self-start" href="{{ $homeUrl }}">
                    {{ __('waitlist.status.return') }}
                </a>
            </div>
        </section>
    </main>
</x-layouts.app>

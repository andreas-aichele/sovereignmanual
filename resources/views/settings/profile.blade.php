<x-settings-layout title="Profile">
    @if (session('toast.message'))
        <div class="mb-6 rounded-md border border-success/30 bg-success/10 p-3 text-sm text-success">{{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="rounded-lg border border-white/10 bg-base-200 p-6">
            <h2 class="text-xl font-semibold">Profile information</h2>
            <p class="mt-1 text-sm text-base-content/70">Update your name and email address.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                    <x-input-error name="name" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                    <x-input-error name="email" />
                </div>

                @if ($mustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="text-sm text-warning">Your email address is unverified.</p>
                @endif

                <button type="submit" class="rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Save</button>
            </form>
        </section>

        <section class="rounded-lg border border-error/30 bg-base-200 p-6">
            <h2 class="text-xl font-semibold text-error">Delete account</h2>
            <p class="mt-1 text-sm text-base-content/70">This permanently deletes your account.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-6 space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete_password" class="block text-sm font-medium">Password</label>
                    <input id="delete_password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                    <x-input-error name="password" />
                </div>

                <button type="submit" class="rounded-md bg-error px-4 py-2 font-semibold text-error-content transition hover:brightness-110">Delete account</button>
            </form>
        </section>
    </div>
</x-settings-layout>

<x-settings-layout title="Profile">
    @if (session('toast.message'))
        <div
            class="border-success/30 bg-success/10 text-success mb-6 rounded-md border p-3 text-sm">
            {{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="bg-base-200 rounded-lg border border-white/10 p-6">
            <h2 class="text-xl font-semibold">Profile information</h2>
            <p class="text-base-content/70 mt-1 text-sm">Update your name and
                email address.</p>

            <form class="mt-6 space-y-4" method="POST"
                action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium"
                        for="name">Name</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="name" name="name" type="text"
                        value="{{ old('name', $user->name) }}" required
                        autocomplete="name">
                    <x-input-error name="name" />
                </div>

                <div>
                    <label class="block text-sm font-medium"
                        for="email">Email</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="email" name="email" type="email"
                        value="{{ old('email', $user->email) }}" required
                        autocomplete="username">
                    <x-input-error name="email" />
                </div>

                @if ($mustVerifyEmail && !$user->hasVerifiedEmail())
                    <p class="text-warning text-sm">Your email address is
                        unverified.</p>
                @endif

                <button
                    class="bg-primary text-primary-content rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
                    type="submit">Save</button>
            </form>
        </section>

        <section class="border-error/30 bg-base-200 rounded-lg border p-6">
            <h2 class="text-error text-xl font-semibold">Delete account</h2>
            <p class="text-base-content/70 mt-1 text-sm">This permanently
                deletes your account.</p>

            <form class="mt-6 space-y-4" method="POST"
                action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-sm font-medium"
                        for="delete_password">Password</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="delete_password" name="password" type="password"
                        required autocomplete="current-password">
                    <x-input-error name="password" />
                </div>

                <button
                    class="bg-error text-error-content rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
                    type="submit">Delete account</button>
            </form>
        </section>
    </div>
</x-settings-layout>

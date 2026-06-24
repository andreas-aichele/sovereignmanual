<x-settings-layout title="Profile">
    @if (session('toast.message'))
        <div class="alert alert-success alert-soft mb-6 text-sm">
            {{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="card card-border bg-base-200">
            <div class="card-body">
                <h2 class="card-title">Profile information</h2>
                <p class="text-base-content/70 text-sm">Update your name and
                    email address.</p>

                <form class="mt-4 space-y-4" method="POST"
                    action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="label" for="name">Name</label>
                        <input class="input w-full" id="name"
                            name="name" type="text"
                            value="{{ old('name', $user->name) }}" required
                            autocomplete="name">
                        <x-input-error name="name" />
                    </div>

                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="input w-full" id="email"
                            name="email" type="email"
                            value="{{ old('email', $user->email) }}" required
                            autocomplete="username">
                        <x-input-error name="email" />
                    </div>

                    @if ($mustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="alert alert-warning alert-soft text-sm">
                            Your email address is unverified.
                        </div>
                    @endif

                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
            </div>
        </section>

        <section class="card card-border border-error/30 bg-base-200">
            <div class="card-body">
                <h2 class="card-title text-error">Delete account</h2>
                <p class="text-base-content/70 text-sm">This permanently
                    deletes your account.</p>

                <form class="mt-4 space-y-4" method="POST"
                    action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label class="label"
                            for="delete_password">Password</label>
                        <input class="input w-full" id="delete_password"
                            name="password" type="password" required
                            autocomplete="current-password">
                        <x-input-error name="password" />
                    </div>

                    <button class="btn btn-error" type="submit">Delete
                        account</button>
                </form>
            </div>
        </section>
    </div>
</x-settings-layout>

<x-auth-layout title="Create account"
    description="Register a new Sovereign Manual account.">
    <form class="space-y-4" method="POST" action="{{ route('register.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-medium" for="name">Name</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="name" name="name" type="text"
                value="{{ old('name') }}" required autofocus
                autocomplete="name">
            <x-input-error name="name" />
        </div>

        <div>
            <label class="block text-sm font-medium"
                for="email">Email</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="email" name="email" type="email"
                value="{{ old('email') }}" required autocomplete="username">
            <x-input-error name="email" />
        </div>

        <div>
            <label class="block text-sm font-medium"
                for="password">Password</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="password" name="password" type="password" required
                autocomplete="new-password">
            <p class="text-base-content/60 mt-2 text-xs">{{ $passwordRules }}
            </p>
            <x-input-error name="password" />
        </div>

        <div>
            <label class="block text-sm font-medium"
                for="password_confirmation">Confirm password</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="password_confirmation" name="password_confirmation"
                type="password" required autocomplete="new-password">
        </div>

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Create account</button>
    </form>

    <p class="text-base-content/70 mt-6 text-sm">
        Already registered?
        <a class="text-primary hover:brightness-110"
            href="{{ route('login') }}">Log in</a>
    </p>
</x-auth-layout>

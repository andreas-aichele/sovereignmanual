<x-auth-layout title="Create account" description="Register a new Sovereign Manual account.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="name" />
        </div>

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="email" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <p class="mt-2 text-xs text-base-content/60">{{ $passwordRules }}</p>
            <x-input-error name="password" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
        </div>

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Create account</button>
    </form>

    <p class="mt-6 text-sm text-base-content/70">
        Already registered?
        <a href="{{ route('login') }}" class="text-primary hover:brightness-110">Log in</a>
    </p>
</x-auth-layout>

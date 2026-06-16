<x-auth-layout title="Log in" description="Access your Sovereign Manual account.">
    <form class="space-y-4" method="POST" action="{{ route('login.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-medium" for="email">Email</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="email" name="email" type="email"
                value="{{ old('email') }}" required autofocus
                autocomplete="username">
            <x-input-error name="email" />
        </div>

        <div>
            <label class="block text-sm font-medium"
                for="password">Password</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="password" name="password" type="password" required
                autocomplete="current-password">
            <x-input-error name="password" />
        </div>

        <label class="text-base-content/75 flex items-center gap-2 text-sm">
            <input class="checkbox checkbox-sm" name="remember" type="checkbox">
            Remember me
        </label>

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Log in</button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-4 text-sm">
        @if ($canResetPassword)
            <a class="text-primary hover:brightness-110"
                href="{{ route('password.request') }}">Forgot password?</a>
        @endif

        <a class="text-base-content/70 hover:text-primary"
            href="{{ route('register') }}">Create account</a>
    </div>
</x-auth-layout>

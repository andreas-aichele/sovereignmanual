<x-auth-layout title="Log in" description="Access your Sovereign Manual account.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="email" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-base-content/75">
            <input type="checkbox" name="remember" class="checkbox checkbox-sm">
            Remember me
        </label>

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Log in</button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-4 text-sm">
        @if ($canResetPassword)
            <a href="{{ route('password.request') }}" class="text-primary hover:brightness-110">Forgot password?</a>
        @endif

        <a href="{{ route('register') }}" class="text-base-content/70 hover:text-primary">Create account</a>
    </div>
</x-auth-layout>

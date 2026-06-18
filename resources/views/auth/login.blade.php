<x-auth-layout title="Log in" description="Access your Sovereign Manual account.">
    <form class="space-y-4" method="POST" action="{{ route('login.store') }}">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="input w-full" id="email" name="email"
                type="email" value="{{ old('email') }}" required autofocus
                autocomplete="username">
            <x-input-error name="email" />
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input class="input w-full" id="password" name="password"
                type="password" required autocomplete="current-password">
            <x-input-error name="password" />
        </div>

        <label class="text-base-content/75 flex items-center gap-2 text-sm">
            <input class="checkbox checkbox-sm" name="remember" type="checkbox">
            Remember me
        </label>

        <button class="btn btn-primary btn-block" type="submit">Log in</button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-4 text-sm">
        @if ($canResetPassword)
            <a class="link link-primary"
                href="{{ route('password.request') }}">Forgot password?</a>
        @endif

        <a class="link link-hover text-base-content/70"
            href="{{ route('register') }}">Create account</a>
    </div>
</x-auth-layout>

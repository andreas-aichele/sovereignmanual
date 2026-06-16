<x-auth-layout title="Reset password"
    description="Choose a new password for your account.">
    <form class="space-y-4" method="POST" action="{{ route('password.update') }}">
        @csrf

        <input name="token" type="hidden" value="{{ $token }}">

        <div>
            <label class="block text-sm font-medium" for="email">Email</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="email" name="email" type="email"
                value="{{ old('email', $email) }}" required
                autocomplete="username">
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
            type="submit">Reset password</button>
    </form>
</x-auth-layout>

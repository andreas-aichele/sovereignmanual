<x-auth-layout title="Reset password"
    description="Choose a new password for your account.">
    <form class="space-y-4" method="POST" action="{{ route('password.update') }}">
        @csrf

        <input name="token" type="hidden" value="{{ $token }}">

        <div>
            <label class="label" for="email">Email</label>
            <input class="input w-full" id="email" name="email"
                type="email" value="{{ old('email', $email) }}" required
                autocomplete="username">
            <x-input-error name="email" />
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input class="input w-full" id="password" name="password"
                type="password" required autocomplete="new-password">
            <p class="label text-base-content/60">{{ $passwordRules }}
            </p>
            <x-input-error name="password" />
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm
                password</label>
            <input class="input w-full" id="password_confirmation"
                name="password_confirmation" type="password" required
                autocomplete="new-password">
        </div>

        <button class="btn btn-primary btn-block" type="submit">Reset
            password</button>
    </form>
</x-auth-layout>

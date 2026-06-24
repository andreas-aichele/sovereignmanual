<x-auth-layout title="Create account"
    description="Register a new Sovereign Manual account.">
    <form class="space-y-4" method="POST" action="{{ route('register.store') }}">
        @csrf

        <div>
            <label class="label" for="name">Name</label>
            <input class="input w-full" id="name" name="name"
                type="text" value="{{ old('name') }}" required autofocus
                autocomplete="name">
            <x-input-error name="name" />
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input class="input w-full" id="email" name="email"
                type="email" value="{{ old('email') }}" required
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

        <button class="btn btn-primary btn-block" type="submit">Create
            account</button>
    </form>

    <p class="text-base-content/70 mt-6 text-sm">
        Already registered?
        <a class="link link-primary" href="{{ route('login') }}">Log in</a>
    </p>
</x-auth-layout>

<x-auth-layout title="Forgot password"
    description="Enter your email address and we will send a password reset link.">
    <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="input w-full" id="email" name="email"
                type="email" value="{{ old('email') }}" required autofocus
                autocomplete="username">
            <x-input-error name="email" />
        </div>

        <button class="btn btn-primary btn-block" type="submit">Email password
            reset link</button>
    </form>
</x-auth-layout>

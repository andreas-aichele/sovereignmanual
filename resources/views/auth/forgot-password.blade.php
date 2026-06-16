<x-auth-layout title="Forgot password"
    description="Enter your email address and we will send a password reset link.">
    <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
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

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Email password reset link</button>
    </form>
</x-auth-layout>

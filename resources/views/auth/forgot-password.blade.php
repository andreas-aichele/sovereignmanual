<x-auth-layout title="Forgot password" description="Enter your email address and we will send a password reset link.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="email" />
        </div>

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Email password reset link</button>
    </form>
</x-auth-layout>

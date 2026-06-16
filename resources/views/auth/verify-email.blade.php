<x-auth-layout title="Verify email"
    description="Please verify your email address before continuing.">
    <form class="space-y-4" method="POST"
        action="{{ route('verification.send') }}">
        @csrf

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Resend verification email</button>
    </form>

    <form class="mt-4" method="POST" action="{{ route('logout') }}">
        @csrf
        <button
            class="hover:border-primary hover:text-primary w-full rounded-md border border-white/15 px-4 py-2 font-semibold"
            type="submit">Log out</button>
    </form>
</x-auth-layout>

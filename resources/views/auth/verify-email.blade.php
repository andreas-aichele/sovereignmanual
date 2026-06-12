<x-auth-layout title="Verify email" description="Please verify your email address before continuing.">
    <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full rounded-md border border-white/15 px-4 py-2 font-semibold hover:border-primary hover:text-primary">Log out</button>
    </form>
</x-auth-layout>

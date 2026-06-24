<x-auth-layout title="Verify email"
    description="Please verify your email address before continuing.">
    <form class="space-y-4" method="POST"
        action="{{ route('verification.send') }}">
        @csrf

        <button class="btn btn-primary btn-block" type="submit">Resend
            verification email</button>
    </form>

    <form class="mt-4" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline btn-block" type="submit">Log
            out</button>
    </form>
</x-auth-layout>

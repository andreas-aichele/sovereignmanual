<x-auth-layout title="Confirm password" description="Confirm your password before continuing.">
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="password" />
        </div>

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Confirm</button>
    </form>
</x-auth-layout>

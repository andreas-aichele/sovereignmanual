<x-auth-layout title="Confirm password"
    description="Confirm your password before continuing.">
    <form class="space-y-4" method="POST"
        action="{{ route('password.confirm.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-medium"
                for="password">Password</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="password" name="password" type="password" required
                autocomplete="current-password">
            <x-input-error name="password" />
        </div>

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Confirm</button>
    </form>
</x-auth-layout>

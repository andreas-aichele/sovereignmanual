<x-auth-layout title="Two-factor challenge"
    description="Enter an authentication code or recovery code.">
    <form class="space-y-4" method="POST"
        action="{{ route('two-factor.login.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-medium" for="code">Authentication
                code</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="code" name="code" type="text" inputmode="numeric"
                autocomplete="one-time-code" autofocus>
            <x-input-error name="code" />
        </div>

        <div>
            <label class="block text-sm font-medium"
                for="recovery_code">Recovery code</label>
            <input
                class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                id="recovery_code" name="recovery_code" type="text"
                autocomplete="one-time-code">
            <x-input-error name="recovery_code" />
        </div>

        <button
            class="bg-primary text-primary-content w-full rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
            type="submit">Continue</button>
    </form>
</x-auth-layout>

<x-auth-layout title="Two-factor challenge" description="Enter an authentication code or recovery code.">
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium">Authentication code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="code" />
        </div>

        <div>
            <label for="recovery_code" class="block text-sm font-medium">Recovery code</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
            <x-input-error name="recovery_code" />
        </div>

        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Continue</button>
    </form>
</x-auth-layout>

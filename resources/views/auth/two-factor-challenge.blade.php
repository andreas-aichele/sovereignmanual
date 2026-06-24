<x-auth-layout title="Two-factor challenge"
    description="Enter an authentication code or recovery code.">
    <form class="space-y-4" method="POST"
        action="{{ route('two-factor.login.store') }}">
        @csrf

        <div>
            <label class="label" for="code">Authentication code</label>
            <input class="input w-full" id="code" name="code"
                type="text" inputmode="numeric" autocomplete="one-time-code"
                autofocus>
            <x-input-error name="code" />
        </div>

        <div>
            <label class="label" for="recovery_code">Recovery code</label>
            <input class="input w-full" id="recovery_code" name="recovery_code"
                type="text" autocomplete="one-time-code">
            <x-input-error name="recovery_code" />
        </div>

        <button class="btn btn-primary btn-block"
            type="submit">Continue</button>
    </form>
</x-auth-layout>

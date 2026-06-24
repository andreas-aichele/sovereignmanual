<x-auth-layout title="Confirm password"
    description="Confirm your password before continuing.">
    <form class="space-y-4" method="POST"
        action="{{ route('password.confirm.store') }}">
        @csrf

        <div>
            <label class="label" for="password">Password</label>
            <input class="input w-full" id="password" name="password"
                type="password" required autocomplete="current-password">
            <x-input-error name="password" />
        </div>

        <button class="btn btn-primary btn-block"
            type="submit">Confirm</button>
    </form>
</x-auth-layout>

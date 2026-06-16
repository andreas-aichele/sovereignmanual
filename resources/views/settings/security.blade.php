<x-settings-layout title="Security">
    @if (session('toast.message'))
        <div
            class="border-success/30 bg-success/10 text-success mb-6 rounded-md border p-3 text-sm">
            {{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="bg-base-200 rounded-lg border border-white/10 p-6">
            <h2 class="text-xl font-semibold">Password</h2>
            <p class="text-base-content/70 mt-1 text-sm">Update the password used
                to sign in.</p>

            <form class="mt-6 space-y-4" method="POST"
                action="{{ route('user-password.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium"
                        for="current_password">Current password</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="current_password" name="current_password"
                        type="password" required
                        autocomplete="current-password">
                    <x-input-error name="current_password" />
                </div>

                <div>
                    <label class="block text-sm font-medium" for="password">New
                        password</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="password" name="password" type="password" required
                        autocomplete="new-password">
                    <p class="text-base-content/60 mt-2 text-xs">
                        {{ $passwordRules }}</p>
                    <x-input-error name="password" />
                </div>

                <div>
                    <label class="block text-sm font-medium"
                        for="password_confirmation">Confirm password</label>
                    <input
                        class="bg-base-300 focus:border-primary mt-2 w-full rounded-md border border-white/10 px-3 py-2 outline-none"
                        id="password_confirmation" name="password_confirmation"
                        type="password" required autocomplete="new-password">
                </div>

                <button
                    class="bg-primary text-primary-content rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
                    type="submit">Update password</button>
            </form>
        </section>

        @if ($canManageTwoFactor)
            <section class="bg-base-200 rounded-lg border border-white/10 p-6">
                <h2 class="text-xl font-semibold">Two-factor authentication</h2>
                <p class="text-base-content/70 mt-1 text-sm">Status:
                    {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}</p>

                @if ($twoFactorEnabled)
                    <form class="mt-6" method="POST"
                        action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <button
                            class="hover:border-primary hover:text-primary rounded-md border border-white/15 px-4 py-2 font-semibold"
                            type="submit">Disable two-factor
                            authentication</button>
                    </form>
                @else
                    <form class="mt-6" method="POST"
                        action="{{ route('two-factor.enable') }}">
                        @csrf
                        <button
                            class="bg-primary text-primary-content rounded-md px-4 py-2 font-semibold transition hover:brightness-110"
                            type="submit">Enable two-factor
                            authentication</button>
                    </form>
                @endif
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="bg-base-200 rounded-lg border border-white/10 p-6">
                <h2 class="text-xl font-semibold">Passkeys</h2>

                @if (count($passkeys) === 0)
                    <p class="text-base-content/70 mt-2 text-sm">No passkeys
                        registered.</p>
                @else
                    <div class="mt-4 divide-y divide-white/10">
                        @foreach ($passkeys as $passkey)
                            <div
                                class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-medium">
                                        {{ $passkey['name'] }}</p>
                                    <p class="text-base-content/60 text-sm">
                                        Created
                                        {{ $passkey['created_at_diff'] }}</p>
                                </div>
                                <form method="POST"
                                    action="{{ route('passkey.destroy', $passkey['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="hover:border-error hover:text-error rounded-md border border-white/15 px-3 py-2 text-sm font-semibold"
                                        type="submit">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-settings-layout>

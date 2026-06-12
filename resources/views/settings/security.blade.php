<x-settings-layout title="Security">
    @if (session('toast.message'))
        <div class="mb-6 rounded-md border border-success/30 bg-success/10 p-3 text-sm text-success">{{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="rounded-lg border border-white/10 bg-base-200 p-6">
            <h2 class="text-xl font-semibold">Password</h2>
            <p class="mt-1 text-sm text-base-content/70">Update the password used to sign in.</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium">Current password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                    <x-input-error name="current_password" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium">New password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                    <p class="mt-2 text-xs text-base-content/60">{{ $passwordRules }}</p>
                    <x-input-error name="password" />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-white/10 bg-base-300 px-3 py-2 outline-none focus:border-primary">
                </div>

                <button type="submit" class="rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Update password</button>
            </form>
        </section>

        @if ($canManageTwoFactor)
            <section class="rounded-lg border border-white/10 bg-base-200 p-6">
                <h2 class="text-xl font-semibold">Two-factor authentication</h2>
                <p class="mt-1 text-sm text-base-content/70">Status: {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}</p>

                @if ($twoFactorEnabled)
                    <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-white/15 px-4 py-2 font-semibold hover:border-primary hover:text-primary">Disable two-factor authentication</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 font-semibold text-primary-content transition hover:brightness-110">Enable two-factor authentication</button>
                    </form>
                @endif
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="rounded-lg border border-white/10 bg-base-200 p-6">
                <h2 class="text-xl font-semibold">Passkeys</h2>

                @if (count($passkeys) === 0)
                    <p class="mt-2 text-sm text-base-content/70">No passkeys registered.</p>
                @else
                    <div class="mt-4 divide-y divide-white/10">
                        @foreach ($passkeys as $passkey)
                            <div class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-medium">{{ $passkey['name'] }}</p>
                                    <p class="text-sm text-base-content/60">Created {{ $passkey['created_at_diff'] }}</p>
                                </div>
                                <form method="POST" action="{{ route('passkey.destroy', $passkey['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-white/15 px-3 py-2 text-sm font-semibold hover:border-error hover:text-error">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-settings-layout>

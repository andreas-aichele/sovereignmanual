<x-settings-layout title="Security">
    @if (session('toast.message'))
        <div class="alert alert-success alert-soft mb-6 text-sm">
            {{ session('toast.message') }}</div>
    @endif

    <div class="space-y-8">
        <section class="card card-border bg-base-200">
            <div class="card-body">
                <h2 class="card-title">Password</h2>
                <p class="text-base-content/70 text-sm">Update the password used
                    to sign in.</p>

                <form class="mt-4 space-y-4" method="POST"
                    action="{{ route('user-password.update') }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label" for="current_password">Current
                            password</label>
                        <input class="input w-full" id="current_password"
                            name="current_password" type="password" required
                            autocomplete="current-password">
                        <x-input-error name="current_password" />
                    </div>

                    <div>
                        <label class="label" for="password">New
                            password</label>
                        <input class="input w-full" id="password"
                            name="password" type="password" required
                            autocomplete="new-password">
                        <p class="label text-base-content/60">
                            {{ $passwordRules }}</p>
                        <x-input-error name="password" />
                    </div>

                    <div>
                        <label class="label"
                            for="password_confirmation">Confirm password</label>
                        <input class="input w-full" id="password_confirmation"
                            name="password_confirmation" type="password"
                            required autocomplete="new-password">
                    </div>

                    <button class="btn btn-primary" type="submit">Update
                        password</button>
                </form>
            </div>
        </section>

        @if ($canManageTwoFactor)
            <section class="card card-border bg-base-200">
                <div class="card-body">
                    <h2 class="card-title">Two-factor authentication</h2>
                    <p class="text-base-content/70 text-sm">Status:
                        <span
                            class="badge {{ $twoFactorEnabled ? 'badge-success' : 'badge-ghost' }}">
                            {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </p>

                    <div class="card-actions mt-4">
                        @if ($twoFactorEnabled)
                            <form method="POST"
                                action="{{ route('two-factor.disable') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline"
                                    type="submit">Disable two-factor
                                    authentication</button>
                            </form>
                        @else
                            <form method="POST"
                                action="{{ route('two-factor.enable') }}">
                                @csrf
                                <button class="btn btn-primary"
                                    type="submit">Enable two-factor
                                    authentication</button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="card card-border bg-base-200">
                <div class="card-body">
                    <h2 class="card-title">Passkeys</h2>

                    @if (count($passkeys) === 0)
                        <p class="text-base-content/70 text-sm">No passkeys
                            registered.</p>
                    @else
                        <ul class="list">
                            @foreach ($passkeys as $passkey)
                                <li class="list-row px-0">
                                    <div class="list-col-grow">
                                        <p class="font-medium">
                                            {{ $passkey['name'] }}</p>
                                        <p class="text-base-content/60 text-sm">
                                            Created
                                            {{ $passkey['created_at_diff'] }}
                                        </p>
                                    </div>
                                    <form method="POST"
                                        action="{{ route('passkey.destroy', $passkey['id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="btn btn-error btn-outline btn-sm"
                                            type="submit">Remove</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-settings-layout>

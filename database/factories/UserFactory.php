<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesAndPermissions();

            if (! $user->roles()->exists()) {
                $user->assignRole('user');
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user can administer the CMS.
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesAndPermissions();
            $user->syncRoles(['admin']);
        });
    }

    /**
     * Indicate that the user can edit CMS content.
     */
    public function editor(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesAndPermissions();
            $user->syncRoles(['editor']);
        });
    }

    private function ensureRolesAndPermissions(): void
    {
        Permission::findOrCreate('access cms', 'web');
        Permission::findOrCreate('manage content', 'web');
        Permission::findOrCreate('manage users', 'web');
        Permission::findOrCreate('view automation', 'web');

        Role::findOrCreate('admin', 'web')
            ->syncPermissions(['access cms', 'manage content', 'manage users', 'view automation']);

        Role::findOrCreate('editor', 'web')
            ->syncPermissions(['access cms', 'manage content', 'view automation']);

        Role::findOrCreate('user', 'web');
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}

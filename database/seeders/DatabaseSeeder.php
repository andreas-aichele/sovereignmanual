<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('access cms', 'web');
        Permission::findOrCreate('manage content', 'web');
        Permission::findOrCreate('manage users', 'web');
        Permission::findOrCreate('view automation', 'web');

        Role::findOrCreate('admin', 'web')
            ->syncPermissions(['access cms', 'manage content', 'manage users', 'view automation']);

        Role::findOrCreate('editor', 'web')
            ->syncPermissions(['access cms', 'manage content', 'view automation']);

        Role::findOrCreate('user', 'web');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => 'password'],
        );

        $admin->syncRoles(['admin']);
    }
}

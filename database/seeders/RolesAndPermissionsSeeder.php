<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();


        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleUser = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);


        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@yopmail.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('11111111'),
                'email_verified_at' => now(),
            ]
        );

        $superAdminUser->assignRole($roleSuperAdmin);
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@yopmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('11111111'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole($roleAdmin);
    }
}

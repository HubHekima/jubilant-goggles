<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;       // <--- ADD THIS
use Spatie\Permission\Models\Permission; // <--- ADD THIS
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // 1. Clear the cache (Essential for Spatie)
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // 2. Define Permissions using firstOrCreate
            $permissions = [
                'manage events',
                'manage ticket types',
                'view sales reports',
                'manage refunds',
                'scan tickets',
                'manage staff',
            ];

            foreach ($permissions as $permission) {
                // This checks if it exists first. If it does, it just moves on.
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            // 3. Create Roles using firstOrCreate
            Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

            $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
            $manager->syncPermissions(['manage events', 'manage ticket types', 'view sales reports']);

            $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
            $staff->syncPermissions(['scan tickets']);

            // 4. Create or Update the Admin User
            User::updateOrCreate(
                ['email' => 'admin@bandname.com'],
                [
                    'name' => 'Admin User',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                ]
            )->assignRole('super-admin');
    }
}

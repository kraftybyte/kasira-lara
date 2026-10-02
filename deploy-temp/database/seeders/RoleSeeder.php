<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Create super_admin role
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Create owner role (tenant owner)
        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->syncPermissions(Permission::all());

        // Create panel_user role
        $panelUser = Role::firstOrCreate(['name' => 'panel_user']);

        // Assign super_admin to all users
        $users = User::all();
        foreach ($users as $user) {
            if (! $user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
            }
        }

        $this->command->info('Shield roles created and super_admin assigned to all users.');
    }
}

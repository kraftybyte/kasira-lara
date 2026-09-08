<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forgetSpatiePermissionCache();

        // Create roles
        $roles = [
            'owner' => 'Owner / Pemilik',
            'manager' => 'Manager',
            'cashier' => 'Kasir',
        ];

        foreach ($roles as $name => $label) {
            Role::firstOrCreate(['name' => $name]);
        }

        // Create permissions for each resource
        $permissions = [
            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            // Ingredients
            'ingredients.view',
            'ingredients.create',
            'ingredients.update',
            'ingredients.delete',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            // Tables
            'tables.view',
            'tables.create',
            'tables.update',
            'tables.delete',

            // Sales
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.delete',

            // Reports
            'reports.view',
            'reports.export',

            // Settings
            'settings.view',
            'settings.update',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to owner
        $owner = Role::where('name', 'owner')->first();
        $owner->givePermissionTo(Permission::all());

        // Assign specific permissions to manager
        $manager = Role::where('name', 'manager')->first();
        $manager->givePermissionTo([
            'products.view', 'products.create', 'products.update',
            'ingredients.view', 'ingredients.create', 'ingredients.update',
            'customers.view', 'customers.create', 'customers.update',
            'tables.view', 'tables.create', 'tables.update',
            'sales.view', 'sales.create', 'sales.update',
            'reports.view', 'reports.export',
            'settings.view',
        ]);

        // Assign specific permissions to cashier
        $cashier = Role::where('name', 'cashier')->first();
        $cashier->givePermissionTo([
            'products.view',
            'customers.view',
            'tables.view',
            'sales.view', 'sales.create',
        ]);
    }
}

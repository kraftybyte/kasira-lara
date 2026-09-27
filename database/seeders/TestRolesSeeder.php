<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        $this->createCashierRole();
        $this->createKitchenRole();
        $this->createKepalaTokoRole();
        $this->createOwnerRole();

        $this->command->info('Test roles created successfully.');
    }

    protected function createCashierRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'cashier']);

        // Pages (lowercase prefix)
        $permissions = [
            'viewAny:POS', 'View:POS',
            'viewAny:Kitchen', 'View:Kitchen',
            'viewAny:Sale', 'View:Sale',
            'viewAny:Dashboard', 'View:Dashboard',

            // Customer - Create + View
            'ViewAny:Customer', 'View:Customer', 'Create:Customer',

            // Reservation - Create + View
            'ViewAny:Reservation', 'View:Reservation', 'Create:Reservation',
        ];

        $this->syncPermissions($role, $permissions);

        $this->command->info('  - cashier role: POS, Kitchen, Sales, Dashboard + Create Customer, Create Reservation');
    }

    protected function createKitchenRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'kitchen']);

        $permissions = [
            'viewAny:Kitchen',
            'View:Kitchen',
        ];

        $this->syncPermissions($role, $permissions);

        $this->command->info('  - kitchen role: Kitchen Display only');
    }

    protected function createKepalaTokoRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'kepala_toko']);

        // Pages (lowercase prefix) - View only
        $permissions = [
            'viewAny:Kitchen', 'View:Kitchen',
            'viewAny:Dashboard', 'View:Dashboard',
            'viewAny:POS', 'View:POS',
            'viewAny:SalesReport', 'View:SalesReport',
            'viewAny:IngredientReport', 'View:IngredientReport',
            'viewAny:TablesOverview', 'View:TablesOverview',

            // Resources (PascalCase) - ViewAny + View + Create (NO update, NO delete)
            'ViewAny:Category', 'View:Category', 'Create:Category',
            'ViewAny:Customer', 'View:Customer', 'Create:Customer',
            'ViewAny:Ingredient', 'View:Ingredient', 'Create:Ingredient',
            'ViewAny:Product', 'View:Product', 'Create:Product',
            'ViewAny:Reservation', 'View:Reservation', 'Create:Reservation',
            'ViewAny:Sale', 'View:Sale', 'Create:Sale',
            'ViewAny:Supplier', 'View:Supplier', 'Create:Supplier',
            'ViewAny:Table', 'View:Table', 'Create:Table',
        ];

        $this->syncPermissions($role, $permissions);

        $this->command->info('  - kepala_toko role: View All (excl Settings/Tenants/Users) + POS + Create only');
    }

    protected function createOwnerRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'owner']);

        // Pages (lowercase prefix) - View only (excludes Role, GlobalSettings)
        $permissions = [
            'viewAny:Kitchen', 'View:Kitchen',
            'viewAny:Dashboard', 'View:Dashboard',
            'viewAny:POS', 'View:POS',
            'viewAny:SalesReport', 'View:SalesReport',
            'viewAny:IngredientReport', 'View:IngredientReport',
            'viewAny:TablesOverview', 'View:TablesOverview',
            'viewAny:TenantSettings', 'View:TenantSettings',

            // Resources (PascalCase) - ViewAny + View + Create only (NO update, NO delete)
            // Excludes: Role, User, Tenant
            'ViewAny:Category', 'View:Category', 'Create:Category',
            'ViewAny:Customer', 'View:Customer', 'Create:Customer',
            'ViewAny:Ingredient', 'View:Ingredient', 'Create:Ingredient',
            'ViewAny:Product', 'View:Product', 'Create:Product',
            'ViewAny:Reservation', 'View:Reservation', 'Create:Reservation',
            'ViewAny:Sale', 'View:Sale', 'Create:Sale',
            'ViewAny:Supplier', 'View:Supplier', 'Create:Supplier',
            'ViewAny:Table', 'View:Table', 'Create:Table',
        ];

        $this->syncPermissions($role, $permissions);

        $this->command->info('  - owner role: View All (excl Roles/GlobalSettings) + Create only');
    }

    protected function syncPermissions(Role $role, array $permissions): void
    {
        $permissionModels = [];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissionModels[] = $permission;
        }

        $role->syncPermissions($permissionModels);
    }

    /**
     * Assign a role to a user by email
     */
    public static function assignRoleToUser(string $email, string $roleName): void
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->assignRole($roleName);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Kitchen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();

    // Create test users for each role
    $this->cashierUser = User::factory()->create();
    $this->cashierUser->assignRole('cashier');

    $this->kitchenUser = User::factory()->create();
    $this->kitchenUser->assignRole('kitchen');

    $this->kepalaTokoUser = User::factory()->create();
    $this->kepalaTokoUser->assignRole('kepala_toko');

    $this->ownerUser = User::factory()->create();
    $this->ownerUser->assignRole('owner');

    $this->superAdminUser = User::factory()->create();
    $this->superAdminUser->assignRole('super_admin');

    // Refresh permissions cache
    app()['cache']->forget('spatie.permission.cache');
});

/**
 * Create test roles with permissions - mirrors TestRolesSeeder.php
 */
function createTestRoles(): void
{
    app()['cache']->forget('spatie.permission.cache');

    // Create roles first
    $roles = ['cashier', 'kitchen', 'kepala_toko', 'owner', 'super_admin'];
    foreach ($roles as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $makePerms = function (array $names): array {
        return collect($names)
            ->map(fn ($name) => Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            ))
            ->all();
    };

    // Create permissions and sync to roles via direct pivot table (bypass Spatie cache)
    $permList = [];
    foreach ([
        // cashier permissions
        ['viewAny:POS', 'View:POS'],
        ['viewAny:Kitchen', 'View:Kitchen'],
        ['ViewAny:Sale', 'View:Sale'],
        ['viewAny:Dashboard', 'View:Dashboard'],
        ['ViewAny:Customer', 'View:Customer', 'Create:Customer'],
        ['ViewAny:Reservation', 'View:Reservation', 'Create:Reservation'],
        // kepala_toko additional permissions
        ['viewAny:SalesReport', 'View:SalesReport'],
        ['viewAny:IngredientReport', 'View:IngredientReport'],
        ['viewAny:TablesOverview', 'View:TablesOverview'],
        ['ViewAny:Category', 'View:Category', 'Create:Category'],
        ['ViewAny:Customer', 'View:Customer', 'Create:Customer'],
        ['ViewAny:Ingredient', 'View:Ingredient', 'Create:Ingredient'],
        ['ViewAny:Product', 'View:Product', 'Create:Product'],
        ['ViewAny:Reservation', 'View:Reservation', 'Create:Reservation'],
        ['ViewAny:Sale', 'View:Sale', 'Create:Sale'],
        ['ViewAny:Supplier', 'View:Supplier', 'Create:Supplier'],
        ['ViewAny:Table', 'View:Table', 'Create:Table'],
        // owner additional permissions
        ['viewAny:POS', 'View:POS'],
        ['viewAny:TenantSettings', 'View:TenantSettings'],
        ['viewAny:SalesReport', 'View:SalesReport'],
        ['viewAny:IngredientReport', 'View:IngredientReport'],
        ['viewAny:TablesOverview', 'View:TablesOverview'],
    ] as $group) {
        foreach ($group as $name) {
            if (! isset($permList[$name])) {
                $permList[$name] = Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['name' => $name, 'guard_name' => 'web']
                );
            }
        }
    }

    // Sync to cashier
    $cashier = Role::findByName('cashier');
    $cashier->permissions()->detach();
    $cashier->permissions()->attach(collect([
        $permList['viewAny:POS'], $permList['View:POS'],
        $permList['viewAny:Kitchen'], $permList['View:Kitchen'],
        $permList['ViewAny:Sale'], $permList['View:Sale'],
        $permList['viewAny:Dashboard'], $permList['View:Dashboard'],
        $permList['ViewAny:Customer'], $permList['View:Customer'], $permList['Create:Customer'],
        $permList['ViewAny:Reservation'], $permList['View:Reservation'], $permList['Create:Reservation'],
    ])->pluck('id')->toArray());

    // Sync to kitchen
    $kitchen = Role::findByName('kitchen');
    $kitchen->permissions()->detach();
    $kitchen->permissions()->attach([
        $permList['viewAny:Kitchen']->id,
        $permList['View:Kitchen']->id,
    ]);

    // Sync to kepala_toko
    $kepalaToko = Role::findByName('kepala_toko');
    $kepalaToko->permissions()->detach();
    $kepalaToko->permissions()->attach(collect([
        $permList['viewAny:Kitchen'], $permList['View:Kitchen'],
        $permList['viewAny:Dashboard'], $permList['View:Dashboard'],
        $permList['viewAny:SalesReport'], $permList['View:SalesReport'],
        $permList['viewAny:IngredientReport'], $permList['View:IngredientReport'],
        $permList['viewAny:TablesOverview'], $permList['View:TablesOverview'],
        $permList['ViewAny:Category'], $permList['View:Category'], $permList['Create:Category'],
        $permList['ViewAny:Customer'], $permList['View:Customer'], $permList['Create:Customer'],
        $permList['ViewAny:Ingredient'], $permList['View:Ingredient'], $permList['Create:Ingredient'],
        $permList['ViewAny:Product'], $permList['View:Product'], $permList['Create:Product'],
        $permList['ViewAny:Reservation'], $permList['View:Reservation'], $permList['Create:Reservation'],
        $permList['ViewAny:Sale'], $permList['View:Sale'], $permList['Create:Sale'],
        $permList['ViewAny:Supplier'], $permList['View:Supplier'], $permList['Create:Supplier'],
        $permList['ViewAny:Table'], $permList['View:Table'], $permList['Create:Table'],
    ])->pluck('id')->toArray());

    // Sync to owner
    $owner = Role::findByName('owner');
    $owner->permissions()->detach();
    $owner->permissions()->attach(collect([
        $permList['viewAny:Kitchen'], $permList['View:Kitchen'],
        $permList['viewAny:Dashboard'], $permList['View:Dashboard'],
        $permList['viewAny:POS'], $permList['View:POS'],
        $permList['viewAny:SalesReport'], $permList['View:SalesReport'],
        $permList['viewAny:IngredientReport'], $permList['View:IngredientReport'],
        $permList['viewAny:TablesOverview'], $permList['View:TablesOverview'],
        $permList['viewAny:TenantSettings'], $permList['View:TenantSettings'],
        $permList['ViewAny:Category'], $permList['View:Category'], $permList['Create:Category'],
        $permList['ViewAny:Customer'], $permList['View:Customer'], $permList['Create:Customer'],
        $permList['ViewAny:Ingredient'], $permList['View:Ingredient'], $permList['Create:Ingredient'],
        $permList['ViewAny:Product'], $permList['View:Product'], $permList['Create:Product'],
        $permList['ViewAny:Reservation'], $permList['View:Reservation'], $permList['Create:Reservation'],
        $permList['ViewAny:Sale'], $permList['View:Sale'], $permList['Create:Sale'],
        $permList['ViewAny:Supplier'], $permList['View:Supplier'], $permList['Create:Supplier'],
        $permList['ViewAny:Table'], $permList['View:Table'], $permList['Create:Table'],
    ])->pluck('id')->toArray());

    // SUPER_ADMIN
    Role::findByName('super_admin');
}

// ============================================================================
// SECTION 1: Permission Existence Tests
// ============================================================================

describe('permission existence', function () {
    test('cashier role has correct permissions assigned', function () {
        $role = Role::findByName('cashier');
        $permissions = $role->getPermissionNames();

        expect($permissions)->toContain('viewAny:POS')
            ->toContain('View:POS')
            ->toContain('viewAny:Kitchen')
            ->toContain('View:Kitchen')
            ->toContain('ViewAny:Sale')
            ->toContain('View:Sale')
            ->toContain('viewAny:Dashboard')
            ->toContain('View:Dashboard')
            ->toContain('ViewAny:Customer')
            ->toContain('View:Customer')
            ->toContain('Create:Customer')
            ->toContain('ViewAny:Reservation')
            ->toContain('View:Reservation')
            ->toContain('Create:Reservation');
    });

    test('kitchen role has ONLY kitchen permissions', function () {
        $role = Role::findByName('kitchen');
        $permissions = $role->getPermissionNames()->toArray();

        expect($permissions)->toBe(['viewAny:Kitchen', 'View:Kitchen']);
    });

    test('kepala_toko role has correct permissions', function () {
        $role = Role::findByName('kepala_toko');
        $permissions = $role->getPermissionNames();

        expect($permissions)->toContain('viewAny:Kitchen')
            ->toContain('viewAny:Dashboard')
            ->toContain('viewAny:SalesReport')
            ->toContain('viewAny:IngredientReport')
            ->toContain('viewAny:TablesOverview');

        foreach (['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Sale', 'Supplier', 'Table'] as $resource) {
            expect($permissions)->toContain("ViewAny:{$resource}")
                ->toContain("View:{$resource}")
                ->toContain("Create:{$resource}")
                ->not->toContain("Update:{$resource}")
                ->not->toContain("Delete:{$resource}");
        }
    });

    test('owner role has correct permissions', function () {
        $role = Role::findByName('owner');
        $permissions = $role->getPermissionNames();

        expect($permissions)->toContain('viewAny:POS')
            ->toContain('viewAny:TenantSettings')
            ->not->toContain('viewAny:GlobalSettings')
            ->not->toContain('ViewAny:Role')
            ->not->toContain('ViewAny:User');

        foreach (['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Sale', 'Supplier', 'Table'] as $resource) {
            expect($permissions)->toContain("ViewAny:{$resource}")
                ->toContain("View:{$resource}")
                ->toContain("Create:{$resource}")
                ->not->toContain("Update:{$resource}")
                ->not->toContain("Delete:{$resource}");
        }
    });
});

// ============================================================================
// SECTION 2: Policy Authorization Tests
// ============================================================================

describe('policy authorization for cashier', function () {
    test('cashier cannot view Category', function () {
        expect($this->cashierUser->can('ViewAny:Category'))->toBeFalse();
        expect($this->cashierUser->can('View:Category'))->toBeFalse();
    });

    test('cashier CAN create Customer', function () {
        expect($this->cashierUser->can('ViewAny:Customer'))->toBeTrue();
        expect($this->cashierUser->can('View:Customer'))->toBeTrue();
        expect($this->cashierUser->can('Create:Customer'))->toBeTrue();
        expect($this->cashierUser->can('Update:Customer'))->toBeFalse();
        expect($this->cashierUser->can('Delete:Customer'))->toBeFalse();
    });

    test('cashier CAN create Reservation', function () {
        expect($this->cashierUser->can('ViewAny:Reservation'))->toBeTrue();
        expect($this->cashierUser->can('View:Reservation'))->toBeTrue();
        expect($this->cashierUser->can('Create:Reservation'))->toBeTrue();
        expect($this->cashierUser->can('Update:Reservation'))->toBeFalse();
        expect($this->cashierUser->can('Delete:Reservation'))->toBeFalse();
    });

    test('cashier can view Sale but not update/delete', function () {
        expect($this->cashierUser->can('ViewAny:Sale'))->toBeTrue();
        expect($this->cashierUser->can('View:Sale'))->toBeTrue();
        expect($this->cashierUser->can('Update:Sale'))->toBeFalse();
        expect($this->cashierUser->can('Delete:Sale'))->toBeFalse();
    });
});

describe('policy authorization for kitchen', function () {
    test('kitchen can ONLY view Kitchen page', function () {
        expect($this->kitchenUser->can('viewAny:Kitchen'))->toBeTrue();
        expect($this->kitchenUser->can('viewAny:Dashboard'))->toBeFalse();
        expect($this->kitchenUser->can('viewAny:POS'))->toBeFalse();
        expect($this->kitchenUser->can('ViewAny:Sale'))->toBeFalse();
    });

    test('kitchen CANNOT access any resource CRUD', function () {
        foreach (['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Sale', 'Supplier', 'Table'] as $resource) {
            expect($this->kitchenUser->can("ViewAny:{$resource}"))->toBeFalse();
        }
    });
});

describe('policy authorization for kepala_toko', function () {
    test('kepala_toko can view/create but NOT update/delete resources', function () {
        foreach (['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Sale', 'Supplier', 'Table'] as $resource) {
            expect($this->kepalaTokoUser->can("ViewAny:{$resource}"))->toBeTrue();
            expect($this->kepalaTokoUser->can("View:{$resource}"))->toBeTrue();
            expect($this->kepalaTokoUser->can("Create:{$resource}"))->toBeTrue();
            expect($this->kepalaTokoUser->can("Update:{$resource}"))->toBeFalse();
            expect($this->kepalaTokoUser->can("Delete:{$resource}"))->toBeFalse();
        }
    });

    test('kepala_toko CANNOT access Settings or Tenants', function () {
        expect($this->kepalaTokoUser->can('viewAny:TenantSettings'))->toBeFalse();
        expect($this->kepalaTokoUser->can('viewAny:GlobalSettings'))->toBeFalse();
        expect($this->kepalaTokoUser->can('ViewAny:Tenant'))->toBeFalse();
        expect($this->kepalaTokoUser->can('ViewAny:User'))->toBeFalse();
    });
});

describe('policy authorization for owner', function () {
    test('owner can view/create but NOT update/delete resources', function () {
        foreach (['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Sale', 'Supplier', 'Table'] as $resource) {
            expect($this->ownerUser->can("ViewAny:{$resource}"))->toBeTrue();
            expect($this->ownerUser->can("View:{$resource}"))->toBeTrue();
            expect($this->ownerUser->can("Create:{$resource}"))->toBeTrue();
            expect($this->ownerUser->can("Update:{$resource}"))->toBeFalse();
            expect($this->ownerUser->can("Delete:{$resource}"))->toBeFalse();
        }
    });

    test('owner CAN access TenantSettings', function () {
        expect($this->ownerUser->can('viewAny:TenantSettings'))->toBeTrue();
    });

    test('owner CANNOT access GlobalSettings', function () {
        expect($this->ownerUser->can('viewAny:GlobalSettings'))->toBeFalse();
    });

    test('owner CANNOT access Roles or Users', function () {
        expect($this->ownerUser->can('ViewAny:Role'))->toBeFalse();
        expect($this->ownerUser->can('ViewAny:User'))->toBeFalse();
    });
});

// ============================================================================
// SECTION 4: Permission Naming Consistency Tests
// ============================================================================

describe('permission naming consistency', function () {
    test('page permissions exist with lowercase prefix', function () {
        expect(Permission::where('name', 'viewAny:POS')->exists())->toBeTrue();
        expect(Permission::where('name', 'viewAny:Kitchen')->exists())->toBeTrue();
        expect(Permission::where('name', 'viewAny:Dashboard')->exists())->toBeTrue();
    });

    test('resource permissions exist with PascalCase prefix', function () {
        expect(Permission::where('name', 'ViewAny:Category')->exists())->toBeTrue();
        expect(Permission::where('name', 'ViewAny:Customer')->exists())->toBeTrue();
    });
});

test('Dashboard canAccess() denies kitchen role and allows cashier role', function () {
    $this->actingAs($this->kitchenUser);
    expect(Dashboard::canAccess())->toBeFalse();

    $this->actingAs($this->cashierUser);
    expect(Dashboard::canAccess())->toBeTrue();
});

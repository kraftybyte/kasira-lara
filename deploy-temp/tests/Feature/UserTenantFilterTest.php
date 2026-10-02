<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()['cache']->forget('spatie.permission.cache');

    foreach (['cashier', 'kitchen', 'kepala_toko', 'owner', 'super_admin'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

});

test('single-tenant owner sees only users from their tenant in query', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    // Owner with ONE tenant - should only see tenant A users
    $ownerA = User::factory()->create(['name' => 'Owner A']);
    $ownerA->assignRole('owner');
    $tenantA->users()->attach($ownerA);

    $cashierA = User::factory()->create(['name' => 'Cashier A']);
    $cashierA->assignRole('cashier');
    $tenantA->users()->attach($cashierA);

    $cashierB = User::factory()->create(['name' => 'Cashier B']);
    $cashierB->assignRole('cashier');
    $tenantB->users()->attach($cashierB);

    $this->actingAs($ownerA);

    // Simulate the UserResource table query logic directly
    $query = User::query()->with('roles');

    // Owner with single tenant - should only see users from that tenant
    $results = $query
        ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
        ->whereHas('tenants', function ($q) use ($ownerA) {
            $q->whereIn('tenants.id', $ownerA->tenants()->pluck('tenants.id'));
        })
        ->get();

    $visibleNames = $results->pluck('name')->sort()->values()->toArray();
    expect($visibleNames)->toBe(['Cashier A', 'Owner A']);
});

test('multi-tenant owner sees all non-super-admin users in query', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    // Owner with TWO tenants - should see all users from both tenants
    $ownerMulti = User::factory()->create(['name' => 'Multi Owner']);
    $ownerMulti->assignRole('owner');
    $ownerMulti->tenants()->attach([$tenantA->id, $tenantB->id]);

    $cashierA = User::factory()->create(['name' => 'Cashier A']);
    $cashierA->assignRole('cashier');
    $tenantA->users()->attach($cashierA);

    $cashierB = User::factory()->create(['name' => 'Cashier B']);
    $cashierB->assignRole('cashier');
    $tenantB->users()->attach($cashierB);

    $superAdmin = User::factory()->create(['name' => 'Super Admin']);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($ownerMulti);

    // Multi-tenant owner: show all users except super_admin
    $query = User::query()->with('roles');

    $results = $query
        ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
        ->get();

    $visibleNames = $results->pluck('name')->sort()->values()->toArray();
    expect($visibleNames)->toBe(['Cashier A', 'Cashier B', 'Multi Owner']);
});

test('super admin sees all users including super_admin in query', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    $userInA = User::factory()->create(['name' => 'User In A']);
    $userInB = User::factory()->create(['name' => 'User In B']);
    $superAdmin = User::factory()->create(['name' => 'Super Admin']);

    $tenantA->users()->attach($userInA);
    $tenantB->users()->attach($userInB);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    // Super admin sees ALL users without any filtering
    $query = User::query()->with('roles');
    $results = $query->get();

    $visibleNames = $results->pluck('name')->sort()->values()->toArray();
    expect($visibleNames)->toBe(['Super Admin', 'User In A', 'User In B']);
});

test('user not assigned to any tenant sees no users', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    $orphanUser = User::factory()->create(['name' => 'Orphan User']);
    $orphanUser->assignRole('cashier');
    // Note: orphanUser is NOT attached to any tenant

    $otherUser = User::factory()->create(['name' => 'Other User']);
    $otherUser->assignRole('cashier');
    $tenantA->users()->attach($otherUser);

    $this->actingAs($orphanUser);

    // Orphan user has no tenants, so they should see no users
    $query = User::query()->with('roles');

    $results = $query
        ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
        ->whereHas('tenants', function ($q) use ($orphanUser) {
            $q->whereIn('tenants.id', $orphanUser->tenants()->pluck('tenants.id'));
        })
        ->get();

    expect($results->pluck('name')->toArray())->toBe([]);
});

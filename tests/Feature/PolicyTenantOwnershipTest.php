<?php

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('only authorizes record actions on records of the active tenant', function () {
    Role::create(['name' => 'owner']);
    Role::findByName('owner')->permissions()->attach(Permission::create(['name' => 'Update:Sale'])->id);
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($a->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($a);

    $saleA = Sale::create(['tenant_id' => $a->id, 'invoice_number' => 'A-1', 'grand_total' => 1]);
    $saleB = Sale::create(['tenant_id' => $b->id, 'invoice_number' => 'B-1', 'grand_total' => 1]);

    expect($owner->can('update', $saleA))->toBeTrue()
        ->and($owner->can('update', $saleB))->toBeFalse();
});

it('keeps deactivated staff out of the panel and off the tenant list', function () {
    $tenant = Tenant::factory()->create();
    $panel = Filament::getPanel('admin');

    $active = User::factory()->create();
    $active->tenants()->attach($tenant->id, ['status' => 'active']);
    $inactive = User::factory()->create();
    $inactive->tenants()->attach($tenant->id, ['status' => 'inactive']);

    expect($active->canAccessPanel($panel))->toBeTrue()
        ->and($active->getTenants($panel))->toHaveCount(1)
        ->and($inactive->canAccessPanel($panel))->toBeFalse()
        ->and($inactive->getTenants($panel))->toHaveCount(0);
});

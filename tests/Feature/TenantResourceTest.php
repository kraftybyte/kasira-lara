<?php

use App\Filament\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\Resources\TenantResource\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function tenantAdmin(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);
    $home = Tenant::factory()->create(['slug' => 'kasira-demo']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->tenants()->attach($home->id, ['status' => 'active']);
    test()->actingAs($admin);
    Filament::setTenant($home);

    return $admin;
}

test('tenant pages live under a short slug', function () {
    tenantAdmin();

    expect(TenantResource::getUrl('create'))->toEndWith('/admin/kasira-demo/tenants/create');
    $this->get(TenantResource::getUrl('create'))->assertOk();
});

test('super admin creates a tenant, slug is derived from the name, and they become a member', function () {
    $admin = tenantAdmin();

    Livewire::test(CreateTenant::class)
        ->fillForm(['name' => 'Warung Bu Sri'])
        ->assertFormSet(['slug' => 'warung-bu-sri'])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = Tenant::where('slug', 'warung-bu-sri')->first();
    expect($tenant)->not->toBeNull()
        ->and($admin->fresh()->tenants()->where('tenants.id', $tenant->id)->wherePivot('status', 'active')->exists())->toBeTrue();
});

test('slug must be a lowercase slug and unique', function () {
    tenantAdmin();

    Livewire::test(CreateTenant::class)
        ->fillForm(['name' => 'Cabang', 'slug' => 'Kasira Demo'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'regex']);

    Livewire::test(CreateTenant::class)
        ->fillForm(['name' => 'Cabang', 'slug' => 'kasira-demo'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(Tenant::count())->toBe(1);
});

test('only super admins see the tenant resource', function () {
    Role::firstOrCreate(['name' => 'owner']);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $this->actingAs($owner);

    expect(TenantResource::canViewAny())->toBeFalse()
        ->and(TenantResource::canCreate())->toBeFalse();
});

<?php

use App\Filament\Pages\Settings\TenantSettings;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('stores the Paywuz key encrypted', function () {
    $tenant = Tenant::factory()->create();
    $setting = TenantSetting::create(['tenant_id' => $tenant->id, 'paywuz_api_key' => 'secret-key']);

    expect(DB::table('tenant_settings')->value('paywuz_api_key'))->not->toBe('secret-key')
        ->and($setting->fresh()->paywuz_api_key)->toBe('secret-key');
});

it('sends the Paywuz key to the browser only for super admins', function () {
    Role::create(['name' => 'owner']);
    Role::create(['name' => 'super_admin']);
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'paywuz_enabled' => true, 'paywuz_api_key' => 'secret-key']);

    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);
    Livewire::test(TenantSettings::class)->assertSet('data.paywuz_api_key', null);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($admin);
    Filament::setTenant($tenant);
    Livewire::test(TenantSettings::class)->assertSet('data.paywuz_api_key', 'secret-key');
});

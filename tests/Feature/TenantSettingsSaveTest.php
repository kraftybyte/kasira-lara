<?php

use App\Filament\Pages\Settings\TenantSettings;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('owner can switch EDC off and set the kitchen SOP minutes', function () {
    Role::firstOrCreate(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);

    Livewire::test(TenantSettings::class)
        ->fillForm(['store_name' => 'Warung Uji', 'payment_edc' => false, 'kitchen_sop_minutes' => 25])
        ->call('save')
        ->assertHasNoFormErrors();

    $setting = TenantSetting::where('tenant_id', $tenant->id)->firstOrFail();
    expect($setting->payment_edc)->toBeFalse()
        ->and($setting->kitchen_sop_minutes)->toBe(25);
});

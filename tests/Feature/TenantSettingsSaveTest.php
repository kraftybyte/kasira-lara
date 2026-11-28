<?php

use App\Filament\Pages\Settings\TenantSettings;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
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

test('the connection test checks the key against the official Paywuz API', function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($admin);
    Filament::setTenant($tenant);
    Http::fake(['api.paywuz.id/v1/payment-methods' => Http::response(['data' => []], 200)]);

    Livewire::test(TenantSettings::class)
        ->fillForm(['paywuz_api_key' => ' pk_live_abc '])
        ->call('testPaywuzConnection')
        ->assertNotified('Koneksi Berhasil');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.paywuz.id/v1/payment-methods' && $request->hasHeader('Authorization', 'Bearer pk_live_abc'));
});

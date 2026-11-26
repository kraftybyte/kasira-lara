<?php

use App\Filament\Pages\Settings\GlobalSettings;
use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('admin pages use the locale chosen in app settings', function () {
    AppSetting::create(['locale' => 'id']);

    $this->get('/admin/login')->assertOk();

    expect(app()->getLocale())->toBe('id');
});

test('admin pages keep the config locale when none is chosen', function () {
    AppSetting::create([]);

    $this->get('/admin/login')->assertOk();

    expect(app()->getLocale())->toBe(config('app.locale'));
});

test('super admin can save the display language from app settings', function () {
    Role::create(['name' => 'super_admin']);
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->tenants()->attach($tenant->id, ['status' => 'active']);

    $this->actingAs($user);
    Filament::setTenant($tenant);

    Livewire::test(GlobalSettings::class)
        ->assertFormFieldExists('locale')
        ->fillForm(['app_name' => 'Kasira', 'locale' => 'en'])
        ->call('save');

    expect(AppSetting::first()->locale)->toBe('en');
});

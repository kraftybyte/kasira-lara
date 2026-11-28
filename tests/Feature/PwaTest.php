<?php

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('the manifest carries the app name, standalone display, and default icons', function () {
    AppSetting::create(['app_name' => 'Warung Kita']);

    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('name', 'Warung Kita')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('start_url', '/admin')
        ->assertJsonPath('icons.1.sizes', '512x512')
        ->assertJsonPath('icons.1.src', url('pwa/icon-512.png'));

    expect(file_get_contents(public_path('sw.js')))->toContain("addEventListener('fetch'");
});

test('a square uploaded logo becomes the home screen icon, a wide wordmark keeps the default', function () {
    Storage::fake('public');
    Storage::disk('public')->putFileAs('app-logos', UploadedFile::fake()->image('wide.png', 300, 120), 'wide.png');
    AppSetting::create(['app_name' => 'Kasira', 'app_logo' => 'app-logos/wide.png']);
    expect($this->get('/manifest.webmanifest')->json('icons.1.src'))->toBe(url('pwa/icon-512.png'));
    AppSetting::query()->delete();

    $logo = UploadedFile::fake()->image('logo.png', 300, 300);
    Storage::disk('public')->putFileAs('app-logos', $logo, 'logo.png');
    AppSetting::create(['app_name' => 'Kasira', 'app_logo' => 'app-logos/logo.png']);

    $json = $this->get('/manifest.webmanifest')->assertOk()->json();

    expect($json['icons'][1]['src'])->toContain('/storage/pwa/')->toEndWith('-512.png');
    $generated = str_replace(Storage::disk('public')->url(''), '', $json['icons'][1]['src']);
    expect(Storage::disk('public')->exists($generated))->toBeTrue();
    [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($generated));
    expect([$w, $h])->toBe([512, 512]);
});

test('admin pages and the kitchen display link the manifest and iOS icon', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    TenantSetting::create(['tenant_id' => $tenant->id, 'kitchen_display_token' => str_repeat('a', 40)]);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);

    $this->get(route('kitchen.display', ['tenant' => 'kasira-demo', 'token' => str_repeat('a', 40)]))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('start=', false)
        ->assertSee('apple-mobile-web-app-capable', false);

    $this->actingAs($owner);
    Filament::setTenant($tenant);
    $this->get(route('filament.admin.pages.kitchen', ['tenant' => 'kasira-demo']))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('apple-touch-icon', false)
        ->assertSee('/sw.js', false);
});

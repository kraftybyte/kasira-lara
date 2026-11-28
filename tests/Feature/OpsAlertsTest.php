<?php

use App\Livewire\OpsAlerts;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function alertsUser(string $role): Tenant
{
    Role::firstOrCreate(['name' => $role]);
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->tenants()->attach($tenant->id, ['status' => 'active']);
    test()->actingAs($user);
    Filament::setTenant($tenant);

    return $tenant;
}

test('the pill lists late orders, QR orders, unpaid counter orders, and empty stock', function () {
    $tenant = alertsUser('owner');
    TenantSetting::create(['tenant_id' => $tenant->id, 'kitchen_sop_minutes' => 15]);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'L-1', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'counter', 'grand_total' => 1000])
        ->forceFill(['created_at' => now()->subMinutes(20)])->save();
    Ingredient::create(['tenant_id' => $tenant->id, 'name' => 'Telur', 'unit' => 'pcs', 'stock' => 0, 'minimum_stock' => 5, 'cost_price' => 0, 'is_active' => true]);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'NG-1', 'name' => 'Nasi Goreng', 'selling_price' => 10000, 'stock' => 0, 'is_active' => true]);

    Livewire::test(OpsAlerts::class)
        ->assertSee('1 pesanan menunggu lebih dari 15 menit (SOP)')
        ->assertSee('1 pesanan dari QR meja, cek dapur')
        ->assertSee('1 pesanan QR menunggu dibayar di kasir')
        ->assertSee('1 bahan baku habis')
        ->assertSee('1 produk habis');
});

test('the pill says all clear when nothing needs attention', function () {
    alertsUser('owner');

    Livewire::test(OpsAlerts::class)
        ->assertSee('Semua aman')
        ->assertSee('Dapur kosong')
        ->assertDontSee('ops-bell-ring');
});

test('kitchen staff only get kitchen alerts', function () {
    $tenant = alertsUser('kitchen');
    Ingredient::create(['tenant_id' => $tenant->id, 'name' => 'Telur', 'unit' => 'pcs', 'stock' => 0, 'minimum_stock' => 5, 'cost_price' => 0, 'is_active' => true]);

    Livewire::test(OpsAlerts::class)
        ->assertDontSee('bahan baku')
        ->assertSee('Semua aman');
});

test('the pill is rendered in the panel top bar', function () {
    $tenant = alertsUser('owner');

    $this->get(route('filament.admin.pages.kitchen', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSeeLivewire(OpsAlerts::class);
});

<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Sidebar labels as "Group / Item" (or just "Item" when ungrouped) for the acting user.
 *
 * @return array<int, string>
 */
function sidebarLabels(User $user, Tenant $tenant): array
{
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);

    $labels = [];
    foreach (Filament::getNavigation() as $group) {
        $groupLabel = (string) $group->getLabel();
        foreach ($group->getItems() as $item) {
            $labels[] = trim($groupLabel.' / '.$item->getLabel(), ' /');
        }
    }

    return $labels;
}

test('super admin sees the sidebar grouped with Indonesian labels', function () {
    $role = Role::create(['name' => 'super_admin']);
    $role->syncPermissions(collect(['Category', 'Customer', 'Ingredient', 'Product', 'Reservation', 'Role', 'Sale', 'Supplier', 'Tenant', 'User'])
        ->map(fn (string $model) => Permission::create(['name' => "ViewAny:{$model}", 'guard_name' => 'web'])));

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->tenants()->attach($tenant->id, ['status' => 'active']);

    expect(sidebarLabels($user, $tenant))->toBe([
        'Dashboard',
        'POS',
        'Meja',
        'Dapur',
        'Penjualan / Transaksi',
        'Penjualan / Reservasi',
        'Penjualan / Pelanggan',
        'Produk & Stok / Produk',
        'Produk & Stok / Kategori',
        'Produk & Stok / Bahan Baku',
        'Produk & Stok / Supplier',
        'Laporan / Laporan Penjualan',
        'Laporan / Laporan Bahan Baku',
        'Pengaturan / Pengaturan Toko',
        'Pengaturan / Pengguna',
        'Pengaturan / Akses',
        'Pengaturan / Pengaturan Aplikasi',
        'Pengaturan / Tenant',
    ]);
});

test('kitchen staff only sees the kitchen display', function () {
    Role::create(['name' => 'kitchen']);
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('kitchen');
    $user->tenants()->attach($tenant->id, ['status' => 'active']);

    expect(sidebarLabels($user, $tenant))->toBe(['Dapur']);
});

<?php

use App\Filament\Pages\Kitchen;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleCancellationService;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function completedSale(bool $stockDeducted): array
{
    $tenant = Tenant::factory()->create();
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    $product = Product::create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'sku' => 'NG-1',
        'name' => 'Nasi Goreng',
        'selling_price' => 25000,
        'stock' => 8,
        'is_active' => true,
    ]);
    $sale = Sale::create([
        'tenant_id' => $tenant->id,
        'invoice_number' => 'INV-C-1',
        'status' => 'completed',
        'payment_status' => 'paid',
        'grand_total' => 50000,
        'stock_deducted_at' => $stockDeducted ? now() : null,
    ]);
    SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => 'Nasi Goreng', 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000, 'total' => 50000]);

    return [$sale, $product];
}

it('restores stock when cancelling a sale whose stock was deducted', function () {
    [$sale, $product] = completedSale(true);

    app(SaleCancellationService::class)->cancel($sale, User::factory()->create()->id, 'salah input');

    expect($sale->fresh())->status->toBe('cancelled')->stock_deducted_at->toBeNull()
        ->and($product->fresh()->stock)->toBe(10);
});

it('does not add stock when cancelling a sale whose stock was never deducted', function () {
    [$sale, $product] = completedSale(false);

    app(SaleCancellationService::class)->cancel($sale, User::factory()->create()->id, 'salah input');

    expect($sale->fresh())->status->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(8);
});

it('removes a cancelled sale from the kitchen screen', function () {
    [$sale] = completedSale(true);
    Role::create(['name' => 'owner']);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($sale->tenant_id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant(Tenant::find($sale->tenant_id));

    expect(Livewire::test(Kitchen::class)->instance()->getPendingOrders()->pluck('id')->all())->toBe([$sale->id]);

    app(SaleCancellationService::class)->cancel($sale, $owner->id, 'salah input');

    expect($sale->fresh()->closed_at)->not->toBeNull()
        ->and(Livewire::test(Kitchen::class)->instance()->getPendingOrders())->toBeEmpty();
});

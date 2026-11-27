<?php

use App\Filament\Pages\POS;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * @return array{0: Tenant, 1: User}
 */
function posOwner(): array
{
    Role::firstOrCreate(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('owner');
    $user->tenants()->attach($tenant->id, ['status' => 'active']);
    test()->actingAs($user);
    Filament::setTenant($tenant);

    return [$tenant, $user];
}

function posProduct(Tenant $tenant, string $name, float $price, float $stock = 10): Product
{
    $category = Category::firstOrCreate(['tenant_id' => $tenant->id, 'slug' => 'umum'], ['name' => 'Umum']);

    return Product::create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'sku' => strtoupper(substr($name, 0, 3)).'-'.uniqid(),
        'name' => $name,
        'selling_price' => $price,
        'stock' => $stock,
        'is_active' => true,
    ]);
}

function openBillItem(Sale $sale, Product $product, float $qty = 1): SaleItem
{
    return SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
        'quantity' => $qty,
        'unit_price' => $product->selling_price,
        'subtotal' => $product->selling_price * $qty,
        'total' => $product->selling_price * $qty,
    ]);
}

it('keeps every item when a table bill is paid at the POS', function () {
    [$tenant] = posOwner();
    $nasi = posProduct($tenant, 'Nasi Goreng', 20000);
    $teh = posProduct($tenant, 'Es Teh', 5000);
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 3', 'slug' => 'meja-3', 'status' => 'active', 'is_active' => true]);
    $bill = Sale::create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'invoice_number' => 'INV-BILL-1',
        'status' => 'open',
        'source' => 'pos',
        'subtotal' => 25000,
        'grand_total' => 25000,
    ]);
    openBillItem($bill, $nasi);
    openBillItem($bill, $teh);

    Livewire::test(POS::class)
        ->call('selectTable', $table->id)
        ->assertSet('currentBillId', $bill->id)
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 30000)
        ->call('processPayment');

    expect($bill->fresh())
        ->status->toBe('completed')
        ->paid_amount->toBe('30000.00')
        ->and($bill->items()->count())->toBe(2)
        ->and($nasi->fresh()->stock)->toBe(9)
        ->and($teh->fresh()->stock)->toBe(9)
        ->and($table->fresh()->status)->toBe('available');
});

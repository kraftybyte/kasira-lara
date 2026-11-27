<?php

use App\Filament\Pages\POS;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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

it('rejects client-side changes to server-only POS state', function () {
    posOwner();

    expect(fn () => Livewire::test(POS::class)->set('currentBillId', 1))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    expect(fn () => Livewire::test(POS::class)->set('cart', ['1' => ['product_id' => 1, 'unit_price' => 1, 'quantity' => 1, 'total' => 1]]))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('ignores a modifier that belongs to another product', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);
    $teh = posProduct($tenant, 'Teh', 8000);
    $extraShot = ProductModifier::create(['product_id' => $teh->id, 'name' => 'Extra', 'type' => 'addon', 'price_adjustment' => 5000, 'is_active' => true]);

    $component = Livewire::test(POS::class)
        ->call('addToCartWithNotes', $kopi, 'catatan', [['id' => $extraShot->id]]);

    $cart = $component->get('cart');
    expect($cart)->toHaveCount(1)
        ->and(array_values($cart)[0]['unit_price'])->toBe(15000.0)
        ->and(array_values($cart)[0]['modifiers'])->toBe([]);
});

it('stores a walk-in cash sale as completed and paid with stock deducted', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);

    Livewire::test(POS::class)
        ->call('addToCart', $kopi)
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 20000)
        ->call('processPayment');

    $sale = Sale::where('source', 'pos')->first();
    expect($sale)->status->toBe('completed')->payment_status->toBe('paid')->stock_deducted_at->not->toBeNull()
        ->change_amount->toBe('5000.00')
        ->and($sale->payments()->count())->toBe(1)
        ->and($kopi->fresh()->stock)->toBe(9);
});

it('creates a fresh pending sale for every QRIS attempt instead of reusing one with the same total', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);
    $teh = posProduct($tenant, 'Teh', 15000);

    Livewire::test(POS::class)->call('addToCart', $kopi)->set('paymentMethod', 'qris')->call('generateQrisPayment');
    Livewire::test(POS::class)->call('addToCart', $teh)->set('paymentMethod', 'qris')->call('generateQrisPayment');

    $pending = Sale::where('status', 'pending')->where('payment_method', 'qris')->with('items')->get();
    expect($pending)->toHaveCount(2)
        ->and($pending->map(fn ($s) => $s->items->first()->product_name)->sort()->values()->all())->toBe(['Kopi', 'Teh']);
});

it('numbers POS invoices consecutively within a day', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);

    foreach ([1, 2] as $i) {
        Livewire::test(POS::class)->call('addToCart', $kopi)->set('paymentMethod', 'cash')->set('paidAmount', 20000)->call('processPayment');
    }

    $prefix = 'INV-'.now()->format('Ymd').'-';
    $sequence = Sale::orderBy('id')->pluck('invoice_number')->map(fn ($n) => explode('-', substr($n, strlen($prefix)))[0]);
    expect($sequence->all())->toBe(['0001', '0002']);
});

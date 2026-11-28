<?php

use App\Filament\Pages\POS;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\SaleCancellationService;
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
        ->and($nasi->fresh()->stock)->toBe(9.0)
        ->and($teh->fresh()->stock)->toBe(9.0)
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
        ->and($kopi->fresh()->stock)->toBe(9.0);
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

it('pays variant lines of the same product separately and checks stock on the total', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000, stock: 3);

    $page = Livewire::test(POS::class)
        ->call('addToCart', $kopi)
        ->call('incrementQuantity', (string) $kopi->id)
        ->call('addToCartWithNotes', $kopi, 'less sugar')
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 50000)
        ->call('processPayment');

    $sale = Sale::where('source', 'pos')->first();
    expect($sale->items()->count())->toBe(2)
        ->and($sale->items()->sum('quantity'))->toBe('3.000')
        ->and((float) $sale->grand_total)->toBe(45000.0)
        ->and($kopi->fresh()->stock)->toBe(0.0);

    // Stok habis: baris berikutnya ditolak sebelum masuk keranjang
    $page->call('addToCart', $kopi);
    expect($page->get('cart'))->toBe([]);
});

it('keeps modifier prices when a table bill is reloaded and stores them as separate rows', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);
    $extra = ProductModifier::create(['product_id' => $kopi->id, 'name' => 'Extra Shot', 'type' => 'addon', 'price_adjustment' => 5000, 'is_active' => true]);
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 7', 'slug' => 'meja-7', 'status' => 'active', 'is_active' => true]);

    Livewire::test(POS::class)
        ->call('selectTable', $table->id)
        ->call('addToCartWithNotes', $kopi, null, [['id' => $extra->id]])
        ->call('saveToTable');

    $bill = Sale::where('table_id', $table->id)->first();
    expect($bill->items()->first())->unit_price->toBe('15000.00')->total->toBe('20000.00')
        ->and($bill->items()->first()->modifiers()->count())->toBe(1);

    $reloaded = Livewire::test(POS::class)->call('selectTable', $table->id);
    $line = array_values($reloaded->get('cart'))[0];
    expect($line['unit_price'])->toBe(20000.0)->and($line['modifiers'][0]['name'])->toBe('Extra Shot');

    $reloaded->set('paymentMethod', 'cash')->set('paidAmount', 20000)->call('processPayment');
    expect($bill->fresh())->status->toBe('completed')->grand_total->toBe('20000.00')
        ->and($bill->items()->first()->modifiers()->count())->toBe(1);
});

it('blocks the plus button when stock is exhausted', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000, stock: 1);

    $page = Livewire::test(POS::class)->call('addToCart', $kopi);
    $page->call('incrementQuantity', (string) $kopi->id);

    expect(array_values($page->get('cart'))[0]['quantity'])->toBe(1);
});

it('reads the tax rate from the store settings used by the customer page', function () {
    [$tenant] = posOwner();
    TenantSetting::create(['tenant_id' => $tenant->id, 'show_tax' => true, 'tax_rate' => 11]);
    $nasi = posProduct($tenant, 'Nasi', 111000);

    $page = Livewire::test(POS::class)->call('addToCart', $nasi)->instance();

    expect($page->taxAmount)->toBe(11000.0)->and($page->total)->toBe(111000.0);
});

it('does not delete a QRIS sale that was paid before the cashier pressed cancel', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);

    $page = Livewire::test(POS::class)->call('addToCart', $kopi)->set('paymentMethod', 'qris')->call('generateQrisPayment');
    $sale = Sale::where('payment_method', 'qris')->first();
    $sale->markPaid('qris', 'TRX-WEBHOOK');

    $page->call('cancelQrisPayment');

    expect(Sale::find($sale->id))->not->toBeNull()->payment_status->toBe('paid')
        ->and($kopi->fresh()->stock)->toBe(9.0);
});

it('awards member points on a cash sale and takes them back on cancellation', function () {
    [$tenant, $user] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);
    $member = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Ani', 'is_member' => true]);

    Livewire::test(POS::class)
        ->call('addToCart', $kopi)
        ->set('customerId', $member->id)
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 15000)
        ->call('processPayment');

    $sale = Sale::where('source', 'pos')->first();
    expect($sale->loyalty_points_awarded)->toBe(15)
        ->and($member->fresh())->points->toBe(15)->total_transactions->toBe(1)->total_spent->toBe('15000.00');

    app(SaleCancellationService::class)->cancel($sale, $user->id, 'batal');

    expect($member->fresh())->points->toBe(0)->total_transactions->toBe(0)->total_spent->toBe('0.00');
});

it('awards member points when a POS QRIS sale is confirmed paid', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 15000);
    $member = Customer::create(['tenant_id' => $tenant->id, 'name' => 'Ani', 'is_member' => true]);

    Livewire::test(POS::class)->call('addToCart', $kopi)->set('customerId', $member->id)->set('paymentMethod', 'qris')->call('generateQrisPayment');
    $sale = Sale::where('payment_method', 'qris')->first();
    $sale->markPaid('qris', 'TRX-1');
    $sale->markPaid('qris', 'TRX-1');

    expect($member->fresh()->points)->toBe(15)->and($sale->fresh()->loyalty_points_awarded)->toBe(15);
});

it('stores new sales as pending by default', function () {
    [$tenant] = posOwner();

    $sale = Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-X', 'grand_total' => 1]);

    expect($sale->fresh()->status)->toBe('pending');
});

it('refuses to pay merged counter orders that changed after the cashier loaded them', function () {
    [$tenant] = posOwner();
    TenantSetting::create(['tenant_id' => $tenant->id, 'table_qr_pay_at_counter' => true]);
    $nasi = posProduct($tenant, 'Nasi Goreng', 20000);
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 5', 'slug' => 'meja-5', 'status' => 'available', 'is_active' => true]);
    $order = fn () => test()->post(route('customer.order.checkout', ['tenant' => $tenant->slug, 'table' => $table->id]), [
        'items' => json_encode([['product_id' => $nasi->id, 'quantity' => 1]]),
        'payment_method' => 'counter',
        'customer_name' => 'Budi',
    ]);

    $order();
    $counter = Sale::where('payment_method', 'counter')->first();
    $page = Livewire::withQueryParams(['bulk' => 'counter', 'table' => (string) $table->id, 'sale_ids' => (string) $counter->id])->test(POS::class);
    expect($page->get('isBulkCounterMode'))->toBeTrue()->and($page->get('cart'))->toHaveCount(1);

    $order(); // pelanggan menambah item setelah kasir memuat keranjang

    $page->set('paymentMethod', 'cash')->set('paidAmount', 100000)->call('processPayment');

    expect(Sale::find($counter->id))->not->toBeNull()
        ->and($counter->items()->count())->toBe(2)
        ->and(Sale::where('source', 'pos')->count())->toBe(0);
});

test('enter in the search box adds the top match and clears the search without a toast', function () {
    [$tenant] = posOwner();
    posProduct($tenant, 'Teh Manis', 5000);
    $kopi = posProduct($tenant, 'Kopi Susu', 15000);

    Livewire::test(POS::class)
        ->set('search', 'kopi')
        ->call('addFirstMatch')
        ->assertSet('search', '')
        ->assertDispatched('cart-updated')
        ->assertNotDispatched('notificationsSent')
        ->assertSet('cart.'.$kopi->id.'.quantity', 1);
});

test('enter with no matching product leaves the cart untouched', function () {
    [$tenant] = posOwner();
    posProduct($tenant, 'Teh Manis', 5000);

    Livewire::test(POS::class)
        ->set('search', 'tidak ada')
        ->call('addFirstMatch')
        ->assertSet('cart', [])
        ->assertNotDispatched('cart-updated');
});

test('cash payment refocuses the search box for the next order', function () {
    [$tenant] = posOwner();
    $kopi = posProduct($tenant, 'Kopi', 10000);

    Livewire::test(POS::class)
        ->call('addToCart', $kopi)
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 10000)
        ->call('processPayment')
        ->assertDispatched('payment-success')
        ->assertDispatched('focus-search');
});

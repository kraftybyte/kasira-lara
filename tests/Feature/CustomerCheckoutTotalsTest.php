<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function taxedFixture(): array
{
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'table_qr_pay_at_counter' => true, 'show_tax' => true, 'tax_rate' => 11, 'paywuz_enabled' => true, 'paywuz_api_key' => 'test-key']);
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 1', 'slug' => 'meja-1', 'status' => 'available', 'is_active' => true]);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'NG-1', 'name' => 'Nasi Goreng', 'selling_price' => 111000, 'stock' => 10, 'is_active' => true]);

    return [$tenant, $table, $product];
}

function order(Tenant $tenant, Table $table, Product $product, int $qty, string $method = 'counter'): TestResponse
{
    return test()->post(route('customer.order.checkout', ['tenant' => $tenant->slug, 'table' => $table->id]), [
        'items' => json_encode([['product_id' => $product->id, 'quantity' => $qty]]),
        'payment_method' => $method,
        'customer_name' => 'Budi',
    ]);
}

it('stores tax-inclusive prices once for a pay-at-counter order', function () {
    [$tenant, $table, $product] = taxedFixture();

    order($tenant, $table, $product, 1)->assertRedirect();

    expect(Sale::first())
        ->subtotal->toBe('100000.00')
        ->tax->toBe('11000.00')
        ->grand_total->toBe('111000.00');
});

it('appends to the pending counter order of the same table and recomputes totals', function () {
    [$tenant, $table, $product] = taxedFixture();
    order($tenant, $table, $product, 1);

    order($tenant, $table, $product, 1)->assertRedirect();

    expect(Sale::count())->toBe(1)
        ->and(Sale::first()->items()->count())->toBe(2)
        ->and(Sale::first())
        ->subtotal->toBe('200000.00')
        ->tax->toBe('22000.00')
        ->grand_total->toBe('222000.00');
});

it('gives consecutive daily invoice numbers', function () {
    [$tenant, $table, $product] = taxedFixture();
    order($tenant, $table, $product, 1, 'transfer');
    order($tenant, $table, $product, 1, 'transfer');

    $prefix = 'INV-'.now()->format('Ymd').'-';
    $numbers = Sale::orderBy('id')->pluck('invoice_number')->map(fn ($n) => explode('-', substr($n, strlen($prefix)))[0]);

    expect($numbers->all())->toBe(['0001', '0002']);
});

it('leaves no order behind when the gateway refuses to create a QR', function () {
    [$tenant, $table, $product] = taxedFixture();
    Http::fake([
        'api.paywuz.id/v1/payment-methods' => Http::response(['data' => [['code' => 'QRIS', 'limits' => ['minIdr' => 1000]]]], 200),
        'api.paywuz.id/v1/transactions' => Http::response(['error' => 'gateway_error'], 500),
    ]);

    order($tenant, $table, $product, 1, 'qris')->assertRedirect();

    expect(Sale::count())->toBe(0);
});

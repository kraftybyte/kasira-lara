<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Http;

/**
 * @return array{0: Tenant, 1: Table}
 */
function qrisTenant(): array
{
    $tenant = Tenant::factory()->create();
    TenantSetting::create([
        'tenant_id' => $tenant->id,
        'paywuz_enabled' => true,
        'paywuz_api_key' => 'test-key',
        'table_qr_qris_auto' => true,
    ]);
    $table = Table::create([
        'tenant_id' => $tenant->id,
        'name' => 'Meja 1',
        'slug' => 'meja-1',
        'status' => 'available',
        'is_active' => true,
    ]);

    return [$tenant, $table];
}

function pendingQrisSale(Tenant $tenant, Table $table): Sale
{
    return Sale::create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'invoice_number' => 'INV-TEST-1',
        'status' => 'pending',
        'source' => 'customer',
        'payment_method' => 'qris',
        'payment_status' => 'pending',
        'paywuz_transaction_id' => 'TRX-1',
        'grand_total' => 50000,
    ]);
}

function confirmUrl(Tenant $tenant, Table $table, Sale $sale): string
{
    return route('customer.order.payment.confirm', ['tenant' => $tenant->slug, 'table' => $table->id, 'sale' => $sale->id]);
}

it('does not mark a QRIS order paid while the gateway says pending', function () {
    [$tenant, $table] = qrisTenant();
    $sale = pendingQrisSale($tenant, $table);
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'pending']], 200)]);

    $this->postJson(confirmUrl($tenant, $table, $sale))
        ->assertOk()
        ->assertJson(['success' => false]);

    expect($sale->fresh())->payment_status->toBe('pending')->status->toBe('pending');
});

it('does not mark a QRIS order paid when the gateway inquiry fails', function () {
    [$tenant, $table] = qrisTenant();
    $sale = pendingQrisSale($tenant, $table);
    Http::fake(['api.paywuz.id/*' => Http::response([], 500)]);

    $this->postJson(confirmUrl($tenant, $table, $sale))
        ->assertOk()
        ->assertJson(['success' => false]);

    expect($sale->fresh())->payment_status->toBe('pending');
});

it('marks a QRIS order paid when the gateway reports success', function () {
    [$tenant, $table] = qrisTenant();
    $sale = pendingQrisSale($tenant, $table);
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'success']], 200)]);

    $this->postJson(confirmUrl($tenant, $table, $sale))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($sale->fresh())->payment_status->toBe('paid');
});

it('stores the gateway transaction id and QR url when a customer checks out with QRIS', function () {
    [$tenant, $table] = qrisTenant();
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Minuman', 'slug' => 'minuman']);
    $product = Product::create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'sku' => 'ES-1',
        'name' => 'Es Teh',
        'selling_price' => 50000,
        'stock' => 10,
        'is_active' => true,
    ]);
    Http::fake([
        'api.paywuz.id/v1/payment-methods' => Http::response(['data' => [['code' => 'QRIS', 'limits' => ['minIdr' => 1000]]]], 200),
        'api.paywuz.id/v1/transactions' => Http::response(['data' => ['id' => 'TRX-9', 'paymentNumber' => '00020101021226QRIS-STRING', 'paymentUrl' => 'https://pay.example/qr', 'status' => 'pending']], 201),
    ]);

    $this->post(route('customer.order.checkout', ['tenant' => $tenant->slug, 'table' => $table->id]), [
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'modifiers' => []]]),
        'payment_method' => 'qris',
        'customer_name' => 'Budi',
    ])->assertRedirect();

    expect(Sale::first())
        ->paywuz_transaction_id->toBe('TRX-9')
        ->paywuz_qr_url->toBe('00020101021226QRIS-STRING');

    // Payload sesuai dokumen Paywuz: biaya ditanggung di sisi kita, metadata pembayar wajib
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/transactions')
        && $request['feeByMerchant'] === true
        && $request['metadata']['customerName'] === 'Budi'
        && $request['amount'] === (int) round(Sale::first()->grand_total));
});

it('refuses QRIS at checkout when the tenant switched it off, even with a key configured', function () {
    [$tenant, $table] = qrisTenant();
    TenantSetting::where('tenant_id', $tenant->id)->update(['table_qr_qris_auto' => false]);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Minuman', 'slug' => 'minuman']);
    $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'ES-2', 'name' => 'Es Teh', 'selling_price' => 5000, 'stock' => 10, 'is_active' => true]);
    Http::fake();

    $this->from(route('customer.order', ['tenant' => $tenant->slug, 'table' => $table->id]))
        ->post(route('customer.order.checkout', ['tenant' => $tenant->slug, 'table' => $table->id]), [
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 1, 'modifiers' => []]]),
            'payment_method' => 'qris',
            'customer_name' => 'Budi',
        ])->assertRedirect()->assertSessionHas('error');

    Http::assertNothingSent();
    expect(Sale::count())->toBe(0);
});

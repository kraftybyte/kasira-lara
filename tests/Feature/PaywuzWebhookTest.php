<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/**
 * @return array{0: Sale, 1: Product}
 */
function webhookSale(): array
{
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'paywuz_enabled' => true, 'paywuz_api_key' => 'test-key']);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    $product = Product::create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'sku' => 'NG-1',
        'name' => 'Nasi Goreng',
        'selling_price' => 25000,
        'stock' => 10,
        'is_active' => true,
    ]);
    $sale = Sale::create([
        'tenant_id' => $tenant->id,
        'invoice_number' => 'INV-WH-1',
        'status' => 'pending',
        'source' => 'customer',
        'payment_method' => 'qris',
        'payment_status' => 'pending',
        'paywuz_transaction_id' => 'TRX-1',
        'subtotal' => 50000,
        'grand_total' => 50000,
    ]);
    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'unit_price' => 25000,
        'subtotal' => 50000,
        'total' => 50000,
    ]);

    return [$sale, $product];
}

/**
 * Payload resmi Paywuz: {event, data: {id, orderId, amount, status, ...}, timestamp}. Kunci data boleh ditimpa.
 */
function signedWebhook(array $data, string $key = 'test-key', ?string $event = null): TestResponse
{
    $data = array_merge(['id' => 'TRX-1', 'orderId' => 'INV-WH-1', 'amount' => 50000, 'paymentMethod' => 'QRIS'], $data);
    $event ??= match ($data['status'] ?? '') {
        'success' => 'transaction.paid', 'failed', 'expired' => 'transaction.failed', default => 'transaction.pending'
    };
    $body = json_encode(['event' => $event, 'data' => $data, 'timestamp' => now()->toIso8601String()]);

    return test()->call('POST', '/webhook/paywuz', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', $body, $key),
        'HTTP_X_PAYWUZ_EVENT' => $event,
        'HTTP_X_PAYWUZ_DELIVERY' => 'dlv-'.uniqid(),
    ], $body);
}

it('marks the sale paid, records the payment, and deducts stock once', function () {
    [$sale, $product] = webhookSale();
    Http::fake(['api.paywuz.id/v1/transactions/INV-WH-1' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);

    signedWebhook(['status' => 'success'])->assertOk();
    signedWebhook(['status' => 'success'])->assertOk();

    expect($sale->fresh())
        ->payment_status->toBe('paid')
        ->status->toBe('completed')
        ->stock_deducted_at->not->toBeNull()
        ->and($sale->payments()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(8.0);
});

it('keeps a paid sale paid when a late expired notification arrives', function () {
    [$sale] = webhookSale();
    Http::fake(['api.paywuz.id/v1/transactions/INV-WH-1' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);
    signedWebhook(['status' => 'success'])->assertOk();

    signedWebhook(['status' => 'expired'])->assertOk();

    expect($sale->fresh())->payment_status->toBe('paid')->status->toBe('completed');
});

it('rejects a success notification whose amount differs from the bill', function () {
    [$sale, $product] = webhookSale();
    Http::fake(['api.paywuz.id/v1/transactions/INV-WH-1' => Http::response(['data' => ['status' => 'success', 'amount' => 40000]], 200)]);

    signedWebhook(['status' => 'success'])->assertStatus(422);

    expect($sale->fresh())->payment_status->toBe('pending')
        ->and($product->fresh()->stock)->toBe(10.0);
});

it('rejects a notification with a bad signature', function () {
    [$sale] = webhookSale();

    signedWebhook(['status' => 'success'], 'wrong-key')->assertStatus(401);

    expect($sale->fresh())->payment_status->toBe('pending');
});

it('records a VA payment with the sale payment method and finds the sale by orderId', function () {
    [$sale] = webhookSale();
    $sale->update(['payment_method' => 'va', 'paywuz_transaction_id' => null]);
    Http::fake(['api.paywuz.id/v1/transactions/INV-WH-1' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);

    signedWebhook(['id' => 'TRX-VA', 'status' => 'success', 'paymentMethod' => 'VA_BCA'])->assertOk();

    expect($sale->fresh())
        ->payment_status->toBe('paid')
        ->paywuz_transaction_id->toBe('TRX-VA')
        ->and($sale->payments()->first()->method)->toBe('va');
});

it('marks a pending sale failed on a failed event without touching stock', function () {
    [$sale, $product] = webhookSale();

    signedWebhook(['status' => 'failed'])->assertOk();

    expect($sale->fresh())->payment_status->toBe('failed')->paywuz_status->toBe('failed')
        ->and($product->fresh()->stock)->toBe(10.0);
});

it('verifies the transaction by orderId at the gateway, not by transaction id', function () {
    [$sale] = webhookSale();
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);

    signedWebhook(['status' => 'success'])->assertOk();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/transactions/INV-WH-1'));
    expect($sale->fresh()->payment_status)->toBe('paid');
});

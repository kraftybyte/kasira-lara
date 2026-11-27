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

function signedWebhook(array $payload, string $key = 'test-key'): TestResponse
{
    $body = json_encode($payload);

    return test()->call('POST', '/webhook/paywuz', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', $body, $key),
    ], $body);
}

it('marks the sale paid, records the payment, and deducts stock once', function () {
    [$sale, $product] = webhookSale();
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);

    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'success'])->assertOk();
    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'success'])->assertOk();

    expect($sale->fresh())
        ->payment_status->toBe('paid')
        ->status->toBe('completed')
        ->stock_deducted_at->not->toBeNull()
        ->and($sale->payments()->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(8);
});

it('keeps a paid sale paid when a late expired notification arrives', function () {
    [$sale] = webhookSale();
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'success', 'amount' => 50000]], 200)]);
    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'success'])->assertOk();

    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'expired'])->assertOk();

    expect($sale->fresh())->payment_status->toBe('paid')->status->toBe('completed');
});

it('rejects a success notification whose amount differs from the bill', function () {
    [$sale, $product] = webhookSale();
    Http::fake(['api.paywuz.id/*' => Http::response(['data' => ['status' => 'success', 'amount' => 40000]], 200)]);

    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'success'])->assertStatus(422);

    expect($sale->fresh())->payment_status->toBe('pending')
        ->and($product->fresh()->stock)->toBe(10);
});

it('rejects a notification with a bad signature', function () {
    [$sale] = webhookSale();

    signedWebhook(['transaction_id' => 'TRX-1', 'status' => 'success'], 'wrong-key')->assertStatus(401);

    expect($sale->fresh())->payment_status->toBe('pending');
});

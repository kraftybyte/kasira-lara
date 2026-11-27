<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Testing\TestResponse;

function checkoutFixture(): array
{
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'table_qr_pay_at_counter' => true]);
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 1', 'slug' => 'meja-1', 'status' => 'available', 'is_active' => true]);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'slug' => 'makanan']);
    $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'NG-1', 'name' => 'Nasi Goreng', 'selling_price' => 50000, 'stock' => 10, 'is_active' => true]);

    return [$tenant, $table, $product];
}

function checkoutPost(Tenant $tenant, Table $table, array $items): TestResponse
{
    return test()->from('/order')->post(route('customer.order.checkout', ['tenant' => $tenant->slug, 'table' => $table->id]), [
        'items' => json_encode($items),
        'payment_method' => 'counter',
        'customer_name' => 'Budi',
    ]);
}

it('rejects a negative quantity instead of lowering the bill', function () {
    [$tenant, $table, $product] = checkoutFixture();

    checkoutPost($tenant, $table, [['product_id' => $product->id, 'quantity' => -2]])
        ->assertRedirect('/order')
        ->assertSessionHasErrors('items.0.quantity');

    expect(Sale::count())->toBe(0);
});

it('rejects an item without a product id instead of crashing', function () {
    [$tenant, $table] = checkoutFixture();

    checkoutPost($tenant, $table, [['quantity' => 1]])
        ->assertRedirect('/order')
        ->assertSessionHasErrors('items.0.product_id');

    expect(Sale::count())->toBe(0);
});

it('rejects a modifier that belongs to another product', function () {
    [$tenant, $table, $product] = checkoutFixture();
    $other = Product::create(['tenant_id' => $tenant->id, 'category_id' => $product->category_id, 'sku' => 'ES-1', 'name' => 'Es Teh', 'selling_price' => 5000, 'stock' => 10, 'is_active' => true]);
    $modifier = ProductModifier::create(['product_id' => $other->id, 'name' => 'Extra', 'type' => 'addon', 'price_adjustment' => 5000, 'is_active' => true]);

    checkoutPost($tenant, $table, [['product_id' => $product->id, 'quantity' => 1, 'modifiers' => [$modifier->id]]])
        ->assertRedirect('/order')
        ->assertSessionHas('error');

    expect(Sale::count())->toBe(0);
});

it('creates a counter order for a valid cart', function () {
    [$tenant, $table, $product] = checkoutFixture();

    checkoutPost($tenant, $table, [['product_id' => $product->id, 'quantity' => 2]])->assertRedirect();

    expect(Sale::first())->grand_total->toBe('100000.00')
        ->and(Sale::first()->items()->count())->toBe(1);
});

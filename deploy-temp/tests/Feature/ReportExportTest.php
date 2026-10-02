<?php

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTenant(): Tenant
{
    return Tenant::create([
        'name' => 'Test Restaurant',
        'slug' => 'test-restaurant',
    ]);
}

function createUserWithTenant(Tenant $tenant): User
{
    $user = User::factory()->create();
    $user->tenants()->attach($tenant);

    return $user;
}

it('exports ingredients stock CSV successfully', function () {
    $tenant = createTenant();
    $user = createUserWithTenant($tenant);

    Ingredient::create([
        'tenant_id' => $tenant->id,
        'name' => 'Tepung Terigu',
        'sku' => 'TT-001',
        'unit' => 'kg',
        'stock' => 50,
        'minimum_stock' => 10,
        'cost_price' => 15000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.ingredients.csv', ['tenant' => $tenant->slug]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition');

    // Verify CSV content contains expected headers and data
    $content = $response->getContent();
    expect($content)->toContain('Nama Bahan');
    expect($content)->toContain('Tepung Terigu');
});

it('exports ingredients usage CSV successfully', function () {
    $tenant = createTenant();
    $user = createUserWithTenant($tenant);

    // Create ingredient
    $ingredient = Ingredient::create([
        'tenant_id' => $tenant->id,
        'name' => 'Tepung Terigu',
        'sku' => 'TT-001',
        'unit' => 'kg',
        'stock' => 50,
        'minimum_stock' => 10,
        'cost_price' => 15000,
        'is_active' => true,
    ]);

    // Create product with ingredient
    $product = Product::create([
        'tenant_id' => $tenant->id,
        'name' => 'Roti Tawar',
        'sku' => 'RT-001',
        'price' => 25000,
        'is_available' => true,
    ]);

    $product->ingredients()->attach($ingredient->id, ['quantity' => 0.5]);

    // Create completed sale
    $sale = Sale::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'invoice_number' => 'INV-'.time(),
        'subtotal' => 25000,
        'tax' => 0,
        'discount' => 0,
        'grand_total' => 25000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'status' => 'completed',
    ]);

    // Create sale item
    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'unit_price' => 25000,
        'subtotal' => 50000,
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.ingredients.usage.csv', [
            'tenant' => $tenant->slug,
            'startDate' => now()->startOfDay()->format('Y-m-d'),
            'endDate' => now()->endOfDay()->format('Y-m-d'),
        ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition');

    // Verify CSV content
    $content = $response->getContent();
    expect($content)->toContain('Nama Bahan');
    expect($content)->toContain('Digunakan');
    expect($content)->toContain('Tepung Terigu');
});

it('returns CSV with headers when no completed sales in date range', function () {
    $tenant = createTenant();
    $user = createUserWithTenant($tenant);

    $response = $this->actingAs($user)
        ->get(route('reports.ingredients.usage.csv', [
            'tenant' => $tenant->slug,
            'startDate' => now()->startOfDay()->format('Y-m-d'),
            'endDate' => now()->endOfDay()->format('Y-m-d'),
        ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // Should return CSV with headers
    $content = $response->getContent();
    expect($content)->toContain('Nama Bahan');
    expect($content)->toContain('Digunakan');
});

it('exports ingredients CSV with search filter', function () {
    $tenant = createTenant();
    $user = createUserWithTenant($tenant);

    Ingredient::create([
        'tenant_id' => $tenant->id,
        'name' => 'Tepung Terigu',
        'sku' => 'TT-001',
        'unit' => 'kg',
        'stock' => 50,
        'cost_price' => 15000,
        'is_active' => true,
    ]);

    Ingredient::create([
        'tenant_id' => $tenant->id,
        'name' => 'Gula Pasir',
        'sku' => 'GP-001',
        'unit' => 'kg',
        'stock' => 30,
        'cost_price' => 12000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.ingredients.csv', [
            'tenant' => $tenant->slug,
            'searchIngredient' => 'Tepung',
        ]));

    $response->assertStatus(200);

    $content = $response->getContent();
    expect($content)->toContain('Tepung Terigu');
    expect($content)->not->toContain('Gula Pasir');
});

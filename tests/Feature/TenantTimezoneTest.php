<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Reports\IngredientReport;
use App\Filament\Pages\Reports\SalesReport;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function ownerOf(Tenant $tenant): User
{
    Role::firstOrCreate(['name' => 'owner']);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    test()->actingAs($owner);
    Filament::setTenant($tenant);

    return $owner;
}

it('counts "today" in the tenant time zone', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-27 23:30:00', 'Asia/Jakarta'));
    $jayapura = Tenant::factory()->create(['timezone' => 'Asia/Jayapura']);
    ownerOf($jayapura);

    $early = Sale::create(['tenant_id' => $jayapura->id, 'invoice_number' => 'E', 'status' => 'completed', 'grand_total' => 1000]);
    $late = Sale::create(['tenant_id' => $jayapura->id, 'invoice_number' => 'L', 'status' => 'completed', 'grand_total' => 1000]);
    $early->forceFill(['created_at' => '2026-11-27 21:00:00'])->saveQuietly(); // 23:00 WIT, still the 27th
    $late->forceFill(['created_at' => '2026-11-27 23:30:00'])->saveQuietly();  // 01:30 WIT, already the 28th

    $report = Livewire::test(SalesReport::class)->instance();

    expect($report->startDate)->toBe('2026-11-28')
        ->and($report->totalSales)->toBe(1);

    Carbon::setTestNow();
});

it('sums every item sold on the dashboard, not only the top products', function () {
    $tenant = Tenant::factory()->create();
    ownerOf($tenant);
    $sale = Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'S', 'status' => 'completed', 'grand_total' => 1]);
    foreach (range(1, 7) as $i) {
        SaleItem::create(['sale_id' => $sale->id, 'product_name' => "P{$i}", 'quantity' => 2, 'unit_price' => 1, 'subtotal' => 2, 'total' => 2]);
    }

    $dashboard = Livewire::test(Dashboard::class)->assertOk()->instance();

    expect($dashboard->totalItemsSold)->toBe(14.0)->and($dashboard->topProducts)->toHaveCount(5);
});

it('computes ingredient usage from sold quantities and recipes', function () {
    $tenant = Tenant::factory()->create();
    ownerOf($tenant);
    $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Minuman', 'slug' => 'minuman']);
    $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'KP', 'name' => 'Kopi', 'selling_price' => 1, 'stock' => 9, 'is_active' => true]);
    $beans = Ingredient::create(['tenant_id' => $tenant->id, 'name' => 'Biji Kopi', 'sku' => 'BK', 'unit' => 'gram', 'stock' => 500, 'cost_price' => 1, 'is_active' => true]);
    $product->ingredients()->attach($beans->id, ['quantity' => 18]);
    $sale = Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'S', 'status' => 'completed', 'grand_total' => 3]);
    SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => 'Kopi', 'quantity' => 3, 'unit_price' => 1, 'subtotal' => 3, 'total' => 3]);

    $usage = collect(Livewire::test(IngredientReport::class)->assertOk()->instance()->ingredientUsage);

    expect($usage->firstWhere('name', 'Biji Kopi')['used'])->toBe(54.0);
});

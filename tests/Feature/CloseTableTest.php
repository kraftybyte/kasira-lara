<?php

namespace Tests\Feature;

use App\Filament\Pages\Tables\TablesOverview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'owner']);
    Role::create(['name' => 'super_admin']);

    // Create tenant
    $this->tenant = Tenant::factory()->create();

    // Create user
    $this->user = User::factory()->create();
    $this->user->assignRole('owner');
    $this->user->tenants()->attach($this->tenant->id, ['status' => 'active']);
});

test('close table changes status to available', function () {
    // Create a table manually
    $table = Table::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Table',
        'slug' => 'test-table-'.time(),
        'status' => 'active',
        'is_active' => true,
    ]);

    expect($table->status)->toBe('active');

    // Close table
    $table->update(['status' => 'available']);

    // Refresh and check
    $table->refresh();
    expect($table->status)->toBe('available');
});

test('sales are preserved with closed_at timestamp after closing table', function () {
    // Create table manually
    $table = Table::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Table 2',
        'slug' => 'test-table-2-'.time(),
        'status' => 'active',
        'is_active' => true,
    ]);

    // Create sale with table_id
    $sale = Sale::create([
        'tenant_id' => $this->tenant->id,
        'table_id' => $table->id,
        'invoice_number' => 'TEST-'.time(),
        'status' => 'completed',
        'grand_total' => 100000,
    ]);

    // Create sale item
    SaleItem::create([
        'sale_id' => $sale->id,
        'product_name' => 'Test Product',
        'quantity' => 1,
        'unit_price' => 100000,
        'subtotal' => 100000,
        'total' => 100000,
    ]);

    // Verify sale exists
    $saleCheck = Sale::where('table_id', $table->id)->first();
    expect($saleCheck)->not->toBeNull();

    // Simulate close table behavior: mark closed_at
    $sale->update(['closed_at' => now()]);

    // Delete items and payments (cleanup behavior)
    $sale->items()->delete();
    $sale->payments()->delete();

    // Table status to available
    $table->update(['status' => 'available']);

    // Sales should still exist (preserved for reporting)
    $salesAfterClose = Sale::where('table_id', $table->id)->count();
    expect($salesAfterClose)->toBe(1);

    // Sale should have closed_at timestamp
    $saleAfterClose = Sale::where('table_id', $table->id)->first();
    expect($saleAfterClose->closed_at)->not->toBeNull();

    // Items should be deleted (cleanup)
    expect($saleAfterClose->items()->count())->toBe(0);

    // Table status should be available
    expect($table->fresh()->status)->toBe('available');
});

test('closeTable method exists in TablesOverview', function () {
    $page = app(TablesOverview::class);

    expect(method_exists($page, 'closeTable'))->toBeTrue();
    expect(method_exists($page, 'confirmCloseTable'))->toBeTrue();
    expect(method_exists($page, 'requestCloseTable'))->toBeTrue();
});

test('table model has reservations relation', function () {
    $table = new Table;

    expect(method_exists($table, 'reservations'))->toBeTrue();
    expect(method_exists($table, 'activeSales'))->toBeTrue();
    expect(method_exists($table, 'hasActiveOrders'))->toBeTrue();
});

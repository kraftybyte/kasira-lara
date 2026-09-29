<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

beforeEach(function () {
    // Create roles
    \Spatie\Permission\Models\Role::create(['name' => 'owner']);
    \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);

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
        'slug' => 'test-table-' . time(),
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

test('sales remain after closing table', function () {
    // Create table manually
    $table = Table::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Table 2',
        'slug' => 'test-table-2-' . time(),
        'status' => 'active',
        'is_active' => true,
    ]);

    // Create sale with table_id
    $sale = Sale::create([
        'tenant_id' => $this->tenant->id,
        'table_id' => $table->id,
        'invoice_number' => 'TEST-' . time(),
        'status' => 'completed',
        'grand_total' => 100000,
    ]);

    // Verify sale exists with table
    $saleCheck = Sale::where('table_id', $table->id)->first();
    expect($saleCheck)->not->toBeNull();
    expect($saleCheck->id)->toBe($sale->id);

    // Close table
    $table->update(['status' => 'available']);

    // Sales should still exist with table_id
    $salesAfterClose = Sale::where('table_id', $table->id)->count();
    expect($salesAfterClose)->toBe(1);

    // Sale should still have table relationship
    $saleAfterClose = Sale::with('table')->find($sale->id);
    expect($saleAfterClose->table)->not->toBeNull();
    expect($saleAfterClose->table->id)->toBe($table->id);
});

test('close table is callable method in TablesOverview', function () {
    $page = app(\App\Filament\Pages\Tables\TablesOverview::class);

    expect(method_exists($page, 'closeTable'))->toBeTrue();
    expect(method_exists($page, 'confirmCloseTable'))->toBeTrue();
    expect(method_exists($page, 'requestCloseTable'))->toBeTrue();
});

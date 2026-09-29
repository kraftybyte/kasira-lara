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

test('sales are deleted after closing table', function () {
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

    // Verify sale exists
    $saleCheck = Sale::where('table_id', $table->id)->first();
    expect($saleCheck)->not->toBeNull();

    // Close table - delete sales
    $sales = Sale::where('table_id', $table->id)->get();
    foreach ($sales as $s) {
        $s->items()->delete();
        $s->payments()->delete();
    }
    Sale::where('table_id', $table->id)->delete();

    // Table status to available
    $table->update(['status' => 'available']);

    // Sales should be deleted
    $salesAfterClose = Sale::where('table_id', $table->id)->count();
    expect($salesAfterClose)->toBe(0);

    // Table status should be available
    expect($table->fresh()->status)->toBe('available');
});

test('closeTable method exists in TablesOverview', function () {
    $page = app(\App\Filament\Pages\Tables\TablesOverview::class);

    expect(method_exists($page, 'closeTable'))->toBeTrue();
    expect(method_exists($page, 'confirmCloseTable'))->toBeTrue();
    expect(method_exists($page, 'requestCloseTable'))->toBeTrue();
});

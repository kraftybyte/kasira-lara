<?php

use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;

/**
 * @return array{0: Tenant, 1: Table}
 */
function statusTable(): array
{
    $tenant = Tenant::factory()->create();
    $table = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 1', 'slug' => 'meja-1', 'status' => 'active', 'is_active' => true]);

    return [$tenant, $table];
}

test('status page walks through unpaid, cooking, ready, and done', function () {
    [$tenant, $table] = statusTable();
    $sale = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-1', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'qris', 'payment_status' => 'pending', 'grand_total' => 10000]);
    $url = route('customer.order.status', [$tenant->slug, $table->id, $sale->id]);

    $this->get($url)->assertOk()->assertSee('Menunggu pembayaran')->assertSee('http-equiv="refresh"', false);

    $sale->update(['payment_status' => 'paid']);
    $this->get($url)->assertSee('Sedang disiapkan');

    $sale->update(['served_at' => now()]);
    $this->get($url)->assertSee('Siap disajikan');

    $sale->update(['kitchen_completed_at' => now()]);
    $this->get($url)->assertSee('Pesanan selesai')->assertDontSee('http-equiv="refresh"', false);
});

test('pay-at-counter orders show as cooking before payment and cancelled orders say so', function () {
    [$tenant, $table] = statusTable();
    $counter = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-2', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'counter', 'payment_status' => 'pending', 'grand_total' => 10000]);
    $cancelled = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-3', 'status' => 'cancelled', 'source' => 'customer', 'payment_method' => 'qris', 'grand_total' => 10000]);

    $counterUrl = route('customer.order.status', [$tenant->slug, $table->id, $counter->id]);
    $this->get($counterUrl)->assertSee('Sedang disiapkan')->assertSee('Bayar di kasir');

    $counter->update(['kitchen_completed_at' => now()]);
    $this->get($counterUrl)->assertSee('Pesanan selesai')->assertSee('Bayar di kasir')->assertSee('http-equiv="refresh"', false);

    $counter->update(['payment_status' => 'paid']);
    $this->get($counterUrl)->assertDontSee('Bayar di kasir')->assertDontSee('http-equiv="refresh"', false);
    $this->get(route('customer.order.status', [$tenant->slug, $table->id, $cancelled->id]))->assertSee('Pesanan dibatalkan');
});

test('status page is scoped to the table that owns the sale', function () {
    [$tenant, $table] = statusTable();
    $otherTable = Table::create(['tenant_id' => $tenant->id, 'name' => 'Meja 2', 'slug' => 'meja-2', 'status' => 'active', 'is_active' => true]);
    $sale = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-4', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'qris', 'grand_total' => 10000]);

    $this->get(route('customer.order.status', [$tenant->slug, $otherTable->id, $sale->id]))->assertNotFound();
});

test('success page and pending banner link to the status page', function () {
    [$tenant, $table] = statusTable();
    $paid = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-5', 'status' => 'completed', 'source' => 'customer', 'payment_method' => 'qris', 'payment_status' => 'paid', 'grand_total' => 10000]);
    $pending = Sale::create(['tenant_id' => $tenant->id, 'table_id' => $table->id, 'invoice_number' => 'ST-6', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'counter', 'grand_total' => 10000]);

    $this->get(route('customer.order.success', [$tenant->slug, $table->id, $paid->id]))
        ->assertOk()
        ->assertSee(route('customer.order.status', [$tenant->slug, $table->id, $paid->id]))
        ->assertDontSee('animate-');
    $this->get(route('customer.order', [$tenant->slug, $table->id]))
        ->assertSee(route('customer.order.status', [$tenant->slug, $table->id, $pending->id]));
});

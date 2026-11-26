<?php

use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;

test('order page renders when the table has a pending pay-at-counter order', function () {
    $tenant = Tenant::factory()->create();

    $table = Table::create([
        'tenant_id' => $tenant->id,
        'name' => 'Meja 1',
        'slug' => 'meja-1',
        'status' => 'active',
        'is_active' => true,
    ]);

    $sale = Sale::create([
        'tenant_id' => $tenant->id,
        'table_id' => $table->id,
        'invoice_number' => 'TEST-PENDING',
        'status' => 'pending',
        'source' => 'customer',
        'payment_method' => 'counter',
        'grand_total' => 50000,
    ]);

    $this->get(route('customer.order', ['tenant' => $tenant->slug, 'table' => $table->id]))
        ->assertOk()
        ->assertSee('Menunggu Pembayaran')
        ->assertSee(route('customer.order.payment', ['tenant' => $tenant->slug, 'table' => $table->id, 'sale' => $sale->id]));
});

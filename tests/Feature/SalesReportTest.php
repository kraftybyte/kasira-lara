<?php

use App\Filament\Pages\Reports\SalesReport;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('totals every matching sale, not only the first page', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);

    foreach (range(1, 120) as $i) {
        Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => "INV-{$i}", 'status' => 'completed', 'payment_method' => $i % 2 ? 'cash' : 'qris', 'subtotal' => 1000, 'grand_total' => 1000]);
    }

    $page = Livewire::test(SalesReport::class)->assertOk()->instance();

    expect($page->totalSales)->toBe(120)
        ->and($page->totalRevenue)->toBe(120000.0)
        ->and($page->salesByPaymentMethod['cash']['count'])->toBe(60)
        ->and($page->sales->total())->toBe(120)
        ->and($page->sales->count())->toBe(100);
});

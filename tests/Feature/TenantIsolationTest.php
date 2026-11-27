<?php

use App\Filament\Pages\Tables\TablesOverview;
use App\Livewire\OrderDetailsModal;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Owner of tenant A acting against records of tenant B.
 *
 * @return array{0: Tenant, 1: Tenant, 2: Table, 3: Sale, 4: Reservation}
 */
function twoTenants(): array
{
    Role::firstOrCreate(['name' => 'owner']);
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($a->id, ['status' => 'active']);
    test()->actingAs($owner);
    Filament::setTenant($a);

    $tableB = Table::create(['tenant_id' => $b->id, 'name' => 'Meja B', 'slug' => 'meja-b', 'status' => 'active', 'is_active' => true]);
    $saleB = Sale::create(['tenant_id' => $b->id, 'table_id' => $tableB->id, 'invoice_number' => 'INV-B-1', 'status' => 'pending', 'source' => 'customer', 'payment_method' => 'counter', 'grand_total' => 10000]);
    $reservationB = Reservation::create(['tenant_id' => $b->id, 'table_id' => $tableB->id, 'customer_name' => 'Tamu B', 'reservation_date' => now()->toDateString(), 'reservation_time' => '19:00', 'guest_count' => 2, 'status' => 'pending']);

    return [$a, $b, $tableB, $saleB, $reservationB];
}

it('cannot close or reserve tables of another tenant from the tables page', function () {
    [, , $tableB, $saleB, $reservationB] = twoTenants();

    Livewire::test(TablesOverview::class)
        ->call('closeTable', $tableB->id)
        ->call('cancelReservation', $reservationB->id)
        ->call('confirmReservation', $reservationB->id)
        ->call('seatReservation', $reservationB->id);

    expect($tableB->fresh()->status)->toBe('active')
        ->and($saleB->fresh()->closed_at)->toBeNull()
        ->and($reservationB->fresh()->status)->toBe('pending');
});

it('cannot open or change orders of another tenant from the order modal', function () {
    [, , $tableB, $saleB] = twoTenants();

    Livewire::test(OrderDetailsModal::class)
        ->call('openModal', $tableB->id, 'Meja B')
        ->assertSet('isOpen', false)
        ->call('markAsServed', $saleB->id);

    expect($saleB->fresh()->served_at)->toBeNull();
});

it('cannot read receipts of another tenant', function () {
    [$a, $b, , $saleB] = twoTenants();
    $saleA = Sale::create(['tenant_id' => $a->id, 'invoice_number' => 'INV-A-1', 'status' => 'completed', 'grand_total' => 5000]);

    $this->get("/admin/{$b->slug}/receipt/{$saleB->id}")->assertNotFound();
    $this->get("/admin/{$a->slug}/receipt/{$saleA->id}")->assertOk();
});

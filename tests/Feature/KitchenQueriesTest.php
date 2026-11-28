<?php

use App\Filament\Pages\Kitchen;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('loads the pending and ready lists once per render', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-1', 'status' => 'pending', 'grand_total' => 1000]);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-2', 'status' => 'pending', 'grand_total' => 1000, 'served_at' => now()]);

    DB::enableQueryLog();
    Livewire::test(Kitchen::class)->assertOk();
    $salesQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from `sales`'))->count();

    expect($salesQueries)->toBe(2);
});

it('alerts the kitchen only when a new pending order appears between polls', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-1', 'status' => 'pending', 'grand_total' => 1000]);

    $page = Livewire::test(Kitchen::class)
        ->call('poll')
        ->assertNotDispatched('kitchen-new-order');

    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-2', 'status' => 'pending', 'grand_total' => 1000]);

    $page->call('poll')
        ->assertDispatched('kitchen-new-order')
        ->call('poll')
        ->assertNotDispatched('kitchen-new-order');
});

it('shows waiting minutes and escalates the colour after 15 minutes', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-OLD', 'status' => 'pending', 'grand_total' => 1000])
        ->forceFill(['created_at' => now()->subMinutes(20)])->save();
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-NEW', 'status' => 'pending', 'grand_total' => 1000])
        ->forceFill(['created_at' => now()->subMinutes(2)])->save();

    Livewire::test(Kitchen::class)
        ->assertSee('20 mnt')
        ->assertSee('2 mnt')
        ->assertSee('bg-red-600')
        ->assertDontSee('bg-amber-500')
        ->assertSee('Aktifkan suara');
});

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

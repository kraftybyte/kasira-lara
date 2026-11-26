<?php

use App\Filament\Pages\POS;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('owner can open the POS page with the single-screen layout', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('owner');
    $user->tenants()->attach($tenant->id, ['status' => 'active']);

    $this->actingAs($user);
    Filament::setTenant($tenant);

    Livewire::test(POS::class)
        ->assertOk()
        ->assertSee('pos-screen')
        ->assertSee('Point of Sale');
});

<?php

use App\Filament\Pages\Kitchen;
use App\Livewire\KitchenDisplay;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('the token link shows the board read-only without login and updates on poll', function () {
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    TenantSetting::create(['tenant_id' => $tenant->id, 'kitchen_display_token' => str_repeat('a', 40)]);
    Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-TV-1', 'status' => 'pending', 'grand_total' => 1000]);
    $url = route('kitchen.display', ['tenant' => 'kasira-demo', 'token' => str_repeat('a', 40)]);

    $this->get($url)
        ->assertOk()
        ->assertSee('INV-TV-1')
        ->assertSee('layar pantau')
        ->assertDontSee('wire:click="markAsServed', false)
        ->assertDontSee('fi-sidebar');

    Livewire::withQueryParams([])->test(KitchenDisplay::class, ['tenant' => 'kasira-demo', 'token' => str_repeat('a', 40)])
        ->call('poll')
        ->assertNotDispatched('kitchen-new-order')
        ->tap(fn () => Sale::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-TV-2', 'status' => 'pending', 'grand_total' => 1000]))
        ->call('poll')
        ->assertDispatched('kitchen-new-order')
        ->assertSee('INV-TV-2');
});

test('a wrong or missing token is a 404', function () {
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    TenantSetting::create(['tenant_id' => $tenant->id, 'kitchen_display_token' => str_repeat('a', 40)]);

    $this->get(route('kitchen.display', ['tenant' => 'kasira-demo', 'token' => str_repeat('b', 40)]))->assertNotFound();

    $other = Tenant::factory()->create(['slug' => 'lain']);
    $this->get(route('kitchen.display', ['tenant' => 'lain', 'token' => str_repeat('a', 40)]))->assertNotFound();
});

test('cashiers get the display link on the kitchen page and can rotate it, kitchen staff do not', function () {
    foreach (['cashier', 'kitchen'] as $role) {
        Role::firstOrCreate(['name' => $role]);
    }
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');
    $cashier->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($cashier);
    Filament::setTenant($tenant);

    $page = Livewire::test(Kitchen::class)->assertSee('Tautan layar pantau');
    $first = TenantSetting::where('tenant_id', $tenant->id)->value('kitchen_display_token');
    expect($first)->toHaveLength(40);

    $page->call('regenerateDisplayToken');
    $second = TenantSetting::where('tenant_id', $tenant->id)->value('kitchen_display_token');
    expect($second)->toHaveLength(40)->not->toBe($first);

    $cook = User::factory()->create();
    $cook->assignRole('kitchen');
    $cook->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($cook);
    Livewire::test(Kitchen::class)->assertDontSee('Tautan layar pantau');
});

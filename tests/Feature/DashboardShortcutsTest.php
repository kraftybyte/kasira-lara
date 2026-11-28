<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

test('the dashboard header carries shortcut icons and the install button', function () {
    Role::create(['name' => 'owner']);
    $tenant = Tenant::factory()->create(['slug' => 'kasira-demo']);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $owner->tenants()->attach($tenant->id, ['status' => 'active']);
    $this->actingAs($owner);
    Filament::setTenant($tenant);

    $this->get(route('filament.admin.pages.dashboard', ['tenant' => 'kasira-demo']))
        ->assertOk()
        ->assertSee('/admin/kasira-demo/pos')
        ->assertSee('/admin/kasira-demo/tables-overview')
        ->assertSee('/admin/kasira-demo/kitchen?display=1')
        ->assertSee('kasira-install-btn')
        ->assertSee('window.kasiraInstall()', false)
        ->assertSee("localStorage.setItem('_x_isOpen', 'false')", false);
});

<?php

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\UserResource\UserResource;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'owner', 'cashier', 'kitchen'] as $name) {
        Role::create(['name' => $name]);
    }
});

it('lets only super admins manage roles', function () {
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $this->actingAs($owner);

    expect(RoleResource::canViewAny())->toBeFalse()
        ->and(RoleResource::canEdit(Role::findByName('cashier')))->toBeFalse()
        ->and(RoleResource::canDelete(Role::findByName('cashier')))->toBeFalse();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    expect(RoleResource::canViewAny())->toBeTrue();
});

it('does not let an owner hand out the owner or super admin role', function () {
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $this->actingAs($owner);

    expect(array_values(UserResource::assignableRoles()))->toBe(['cashier', 'kitchen']);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    expect(UserResource::assignableRoles())->toHaveCount(4);
});

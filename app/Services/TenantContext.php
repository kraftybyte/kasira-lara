<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class TenantContext
{
    public function set(User $user, Tenant $tenant): void
    {
        abort_unless(
            $user->tenants()->whereKey($tenant->id)->exists(),
            403
        );

        session([
            'active_tenant_id' => $tenant->id,
        ]);

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($tenant->id);
    }

    public function current(): ?Tenant
    {
        $tenantId = session('active_tenant_id');

        if (! $tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }

    public function clear(): void
    {
        session()->forget('active_tenant_id');

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);
    }
}

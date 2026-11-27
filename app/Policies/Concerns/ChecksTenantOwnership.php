<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Lapisan kedua di bawah query ter-scope Filament: izin per-record hanya berlaku untuk record tenant aktif.
 */
trait ChecksTenantOwnership
{
    protected function ownedByTenant(Model $model): bool
    {
        $tenantId = Filament::getTenant()?->getKey();

        return $tenantId !== null && (int) $model->getAttribute('tenant_id') === (int) $tenantId;
    }
}

<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\TenantSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data papan dapur, dipakai halaman Dapur (admin) dan layar pantau bertoken.
 */
class KitchenBoard
{
    public static function pending(int $tenantId): Collection
    {
        return self::open($tenantId)->whereNull('served_at')->get();
    }

    public static function ready(int $tenantId): Collection
    {
        return self::open($tenantId)->whereNotNull('served_at')->get();
    }

    public static function sopMinutes(int $tenantId): int
    {
        return max(1, (int) (TenantSetting::where('tenant_id', $tenantId)->value('kitchen_sop_minutes') ?? 15));
    }

    protected static function open(int $tenantId): Builder
    {
        return Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('closed_at')
            ->whereNull('kitchen_completed_at')
            // Pesanan QRIS/VA baru masuk dapur setelah lunas; yang kedaluwarsa tidak pernah muncul
            ->where(fn (Builder $q) => $q->whereNull('payment_method')->orWhereNotIn('payment_method', ['qris', 'va'])->orWhere('payment_status', 'paid'))
            ->with(['items.modifiers', 'table'])
            ->orderBy('created_at');
    }
}

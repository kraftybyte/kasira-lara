<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Table extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'table_number',
        'status',
        'capacity',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Table $table) {
            if (blank($table->tenant_id)) {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    $table->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get active (non-completed, non-cancelled) sales for this table.
     */
    public function activeSales(): HasMany
    {
        return $this->hasMany(Sale::class)
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Check if table has any active (non-completed) orders.
     */
    public function hasActiveOrders(): bool
    {
        return $this->activeSales()->exists();
    }

    public function getActiveBillAttribute(): ?Sale
    {
        // SECURITY: Add tenant_id scoping to prevent cross-tenant data leak
        return Sale::query()
            ->where('tenant_id', $this->tenant_id)
            ->where('table_id', $this->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest('created_at')
            ->first();
    }

    public function getPendingOrdersAttribute(): Collection
    {
        // SECURITY: Add tenant_id scoping to prevent cross-tenant data leak
        return Sale::query()
            ->where('tenant_id', $this->tenant_id)
            ->where('table_id', $this->id)
            ->where('status', 'completed')
            ->latest('created_at')
            ->get();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if table is truly available based on orders.
     * A table is available only if:
     * 1. Status is 'available'
     * 2. Has NO active orders (pending/open/completed with closed_at=null)
     */
    public function isActuallyAvailable(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        return ! $this->hasActiveOrders();
    }

    /**
     * Check if table is actually in use (has active orders).
     */
    public function isActuallyOccupied(): bool
    {
        return $this->hasActiveOrders();
    }

    /**
     * Get display status label based on actual order state.
     */
    public function getDisplayStatus(): string
    {
        if ($this->status === 'reserved') {
            return 'Dipesan';
        }

        if ($this->hasActiveOrders()) {
            return 'Digunakan';
        }

        return 'Tersedia';
    }
}

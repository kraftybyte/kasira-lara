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

    public function getActiveBillAttribute(): ?Sale
    {
        return Sale::query()
            ->where('table_id', $this->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest()
            ->first();
    }

    public function getPendingOrdersAttribute(): Collection
    {
        return Sale::query()
            ->where('table_id', $this->id)
            ->where('status', 'completed')
            ->latest()
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
}

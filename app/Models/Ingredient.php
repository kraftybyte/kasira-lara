<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'unit',
        'stock',
        'minimum_stock',
        'cost_price',
        'is_active',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ingredient $ingredient) {
            if (blank($ingredient->tenant_id)) {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    $ingredient->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        if ($this->minimum_stock === null) {
            return false;
        }

        return (float) $this->stock <= (float) $this->minimum_stock;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->stock <= 0;
    }
}

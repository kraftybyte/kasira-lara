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
        'reorder_point',
        'auto_reorder',
        'cost_price',
        'is_active',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'reorder_point' => 'decimal:3',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'auto_reorder' => 'boolean',
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

    public function needsReorder(): bool
    {
        if (! $this->auto_reorder) {
            return false;
        }

        $reorderPoint = (float) ($this->reorder_point ?? $this->minimum_stock ?? 0);

        return (float) $this->stock <= $reorderPoint;
    }

    public function getSuggestedReorderQuantity(): float
    {
        if (! $this->needsReorder()) {
            return 0;
        }

        $reorderPoint = (float) ($this->reorder_point ?? $this->minimum_stock ?? 0);
        $suggestedQty = ($reorderPoint * 2) - (float) $this->stock;

        return max(0, $suggestedQty);
    }

    public function getReorderUrgency(): string
    {
        if ($this->isOutOfStock()) {
            return 'critical';
        }

        $reorderPoint = (float) ($this->reorder_point ?? $this->minimum_stock ?? 0);
        if ($reorderPoint <= 0) {
            return 'none';
        }

        $ratio = (float) $this->stock / $reorderPoint;

        if ($ratio <= 0.5) {
            return 'urgent';
        }

        if ($ratio <= 1) {
            return 'soon';
        }

        return 'ok';
    }
}

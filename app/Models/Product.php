<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image',
        'cost_price',
        'selling_price',
        'rate_type',
        'rate',
        'stock',
        'minimum_stock',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'rate' => 'decimal:2',
            'stock' => 'integer',
            'minimum_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (blank($product->tenant_id)) {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    $product->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function hasIngredients(): bool
    {
        return $this->ingredients()->exists();
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function allModifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class)->orderBy('sort_order');
    }

    public function addonModifiers(): HasMany
    {
        return $this->modifiers()->where('type', 'addon');
    }

    public function optionModifiers(): HasMany
    {
        return $this->modifiers()->where('type', 'option');
    }
}

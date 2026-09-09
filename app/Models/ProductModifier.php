<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModifier extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'type',
        'price_adjustment',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isAddon(): bool
    {
        return $this->type === 'addon';
    }

    public function isOption(): bool
    {
        return $this->type === 'option';
    }
}

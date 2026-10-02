<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemModifier extends Model
{
    protected $fillable = [
        'sale_item_id',
        'product_modifier_id',
        'modifier_name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function productModifier(): BelongsTo
    {
        return $this->belongsTo(ProductModifier::class);
    }
}

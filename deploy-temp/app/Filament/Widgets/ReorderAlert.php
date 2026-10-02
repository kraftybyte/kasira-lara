<?php

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use Filament\Widgets\Widget;

class ReorderAlert extends Widget
{
    public function getRecords(): array
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return [];
        }

        return Ingredient::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('supplier_id')
            ->whereRaw('stock <= minimum_stock')
            ->with('supplier')
            ->orderByRaw('stock / NULLIF(minimum_stock, 0) ASC')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'stock' => (float) $item->stock,
                'minimum' => (float) $item->minimum_stock,
                'unit' => $item->unit,
                'supplier' => $item->supplier?->name,
                'supplier_phone' => $item->supplier?->phone,
            ])
            ->toArray();
    }
}

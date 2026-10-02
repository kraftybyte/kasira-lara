<?php

namespace App\Filament\Pages\Reports;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class IngredientReport extends Page
{
    protected string $view = 'filament.pages.reports.ingredient-report';

    protected static ?string $title = 'Laporan Bahan Baku';

    protected static ?string $slug = 'ingredient-report';

    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only: super_admin, owner, kepala_toko can access Ingredient Report
        return $user->hasAnyRole(['super_admin', 'owner', 'kepala_toko']);
    }

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    public string $dateRange = 'today';

    public string $startDate = '';

    public string $endDate = '';

    public ?string $categoryFilter = null;

    public string $searchIngredient = '';

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->startDate = now()->startOfDay()->format('Y-m-d');
        $this->endDate = now()->endOfDay()->format('Y-m-d');
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED
    |--------------------------------------------------------------------------
    */

    public function getTenantProperty(): ?Tenant
    {
        return Filament::getTenant();
    }

    public function getStartDateTime(): Carbon
    {
        return Carbon::parse($this->startDate)->startOfDay();
    }

    public function getEndDateTime(): Carbon
    {
        return Carbon::parse($this->endDate)->endOfDay();
    }

    public function getIngredientsProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        $query = Ingredient::query()
            ->with('supplier')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true);

        if (! empty($this->searchIngredient)) {
            $query->where('name', 'like', '%'.$this->searchIngredient.'%');
        }

        return $query->orderBy('name')->get();
    }

    public function getLowStockIngredientsProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        return Ingredient::query()
            ->with('supplier')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('minimum_stock')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('stock / NULLIF(minimum_stock, 0) ASC')
            ->get();
    }

    public function getOutOfStockIngredientsProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        return Ingredient::query()
            ->with('supplier')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->get();
    }

    public function getIngredientUsageProperty(): array
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return [];
        }

        // Get sales in date range
        $sales = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [
                $this->getStartDateTime(),
                $this->getEndDateTime(),
            ])
            ->where('status', 'completed')
            ->pluck('id');

        if ($sales->isEmpty()) {
            return [];
        }

        // Get sale items
        $saleItems = SaleItem::whereIn('sale_id', $sales)->get();

        if ($saleItems->isEmpty()) {
            return [];
        }

        // Get products with ingredients
        $soldProductIds = $saleItems->pluck('product_id')->filter()->unique();

        $usage = [];

        foreach ($soldProductIds as $productId) {
            $product = Product::with('ingredients')->find($productId);

            if (! $product || $product->ingredients->isEmpty()) {
                continue;
            }

            $totalQty = $saleItems->where('product_id', $productId)->sum('quantity');

            foreach ($product->ingredients as $ingredient) {
                $used = (float) $ingredient->pivot->quantity * $totalQty;
                $key = $ingredient->id;

                if (isset($usage[$key])) {
                    $usage[$key]['used'] += $used;
                    $usage[$key]['products'][] = $product->name;
                } else {
                    $usage[$key] = [
                        'id' => $ingredient->id,
                        'name' => $ingredient->name,
                        'unit' => $ingredient->unit,
                        'stock' => (float) $ingredient->stock,
                        'minimum_stock' => (float) ($ingredient->minimum_stock ?? 0),
                        'cost_price' => (float) $ingredient->cost_price,
                        'used' => $used,
                        'remaining' => (float) $ingredient->stock - $used,
                        'supplier' => $ingredient->supplier?->name,
                        'products' => [$product->name],
                    ];
                }
            }
        }

        // Sort by usage (highest first)
        usort($usage, fn ($a, $b) => $b['used'] <=> $a['used']);

        return $usage;
    }

    public function getTotalIngredientUsageValueProperty(): float
    {
        $usage = $this->ingredientUsage;

        return collect($usage)->sum(function ($item) {
            return $item['used'] * $item['cost_price'];
        });
    }

    public function getTotalIngredientStockValueProperty(): float
    {
        return $this->ingredients->sum(function ($ingredient) {
            return (float) $ingredient->stock * (float) $ingredient->cost_price;
        });
    }

    public function getTotalLowStockCountProperty(): int
    {
        return $this->lowStockIngredients->count();
    }

    public function getTotalOutOfStockCountProperty(): int
    {
        return $this->outOfStockIngredients->count();
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;

        switch ($range) {
            case 'today':
                $this->startDate = now()->startOfDay()->format('Y-m-d');
                $this->endDate = now()->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = now()->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = now()->subDay()->endOfDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->endOfMonth()->format('Y-m-d');
                break;
        }
    }
}

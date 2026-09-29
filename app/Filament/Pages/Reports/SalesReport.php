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
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use UnitEnum;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.reports.sales-report';

    protected static ?string $title = 'Laporan Penjualan';

    protected static ?string $slug = 'sales-report';

    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only: super_admin, owner, kepala_toko can access Sales Report
        // NOT: cashier, kitchen
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

    public ?string $statusFilter = null;

    public ?string $paymentMethodFilter = null;

    public string $tableSearch = '';

    protected $paginationTheme = 'bootstrap';

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

    public function getSalesProperty(): Paginator
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $query = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [
                $this->getStartDateTime(),
                $this->getEndDateTime(),
            ]);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }

        if ($this->tableSearch) {
            $search = $this->tableSearch;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->with(['customer', 'user', 'table', 'payments'])
            ->orderByDesc('created_at')
            ->paginate(100);
    }

    public function getTotalSalesProperty(): int
    {
        return $this->sales->count();
    }

    public function getTotalRevenueProperty(): float
    {
        return (float) $this->sales->sum('grand_total');
    }

    public function getTotalTaxProperty(): float
    {
        return (float) $this->sales->sum('tax');
    }

    public function getTotalDiscountProperty(): float
    {
        return (float) $this->sales->sum('discount');
    }

    public function getTotalSubtotalProperty(): float
    {
        return (float) $this->sales->sum('subtotal');
    }

    public function getAverageTransactionProperty(): float
    {
        $count = $this->totalSales;
        if ($count === 0) {
            return 0;
        }

        return $this->totalRevenue / $count;
    }

    public function getTotalProfitProperty(): float
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            return 0;
        }

        $saleIds = $this->sales->pluck('id');
        if ($saleIds->isEmpty()) {
            return 0;
        }

        // Calculate profit from sale items
        $totalCost = SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->get()
            ->sum(function ($item) {
                $product = $item->product;
                if (! $product) {
                    return 0;
                }

                // Cost = product cost_price * quantity
                return (float) $product->cost_price * (float) $item->quantity;
            });

        return max(0, $this->totalRevenue - $totalCost);
    }

    public function getProfitMarginProperty(): float
    {
        if ($this->totalRevenue <= 0) {
            return 0;
        }

        return ($this->totalProfit / $this->totalRevenue) * 100;
    }

    public function getSalesByPaymentMethodProperty(): array
    {
        return [
            'cash' => [
                'label' => 'Tunai',
                'icon' => 'banknotes',
                'color' => 'emerald',
                'count' => $this->sales->where('payment_method', 'cash')->count(),
                'amount' => $this->sales->where('payment_method', 'cash')->sum('grand_total'),
            ],
            'qris' => [
                'label' => 'QRIS Auto',
                'icon' => 'qr-code',
                'color' => 'blue',
                'count' => $this->sales->where('payment_method', 'qris')->count(),
                'amount' => $this->sales->where('payment_method', 'qris')->sum('grand_total'),
            ],
            'qris_manual' => [
                'label' => 'QRIS Manual',
                'icon' => 'qr-code',
                'color' => 'purple',
                'count' => $this->sales->where('payment_method', 'qris_manual')->count(),
                'amount' => $this->sales->where('payment_method', 'qris_manual')->sum('grand_total'),
            ],
            'va' => [
                'label' => 'Virtual Account',
                'icon' => 'building-office',
                'color' => 'indigo',
                'count' => $this->sales->where('payment_method', 'va')->count(),
                'amount' => $this->sales->where('payment_method', 'va')->sum('grand_total'),
            ],
            'transfer' => [
                'label' => 'Transfer',
                'icon' => 'building-library',
                'color' => 'violet',
                'count' => $this->sales->where('payment_method', 'transfer')->count(),
                'amount' => $this->sales->where('payment_method', 'transfer')->sum('grand_total'),
            ],
            'transfer_manual' => [
                'label' => 'Transfer Manual',
                'icon' => 'building-library',
                'color' => 'pink',
                'count' => $this->sales->where('payment_method', 'transfer_manual')->count(),
                'amount' => $this->sales->where('payment_method', 'transfer_manual')->sum('grand_total'),
            ],
        ];
    }

    public function getTopProductsProperty(): Collection
    {
        $saleIds = $this->sales->pluck('id');

        return SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(total) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
    }

    public function getLowStockIngredientsProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        return Ingredient::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('minimum_stock')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('stock / NULLIF(minimum_stock, 0) ASC')
            ->limit(10)
            ->get();
    }

    public function getIngredientUsageProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        $saleIds = $this->sales->pluck('id');

        if ($saleIds->isEmpty()) {
            return collect();
        }

        // Get products sold in this period
        $soldProductIds = SaleItem::whereIn('sale_id', $saleIds)
            ->pluck('product_id')
            ->filter()
            ->unique();

        // Calculate ingredient usage
        $usage = [];
        foreach ($soldProductIds as $productId) {
            $product = Product::with('ingredients')->find($productId);

            if (! $product || $product->ingredients->isEmpty()) {
                continue;
            }

            $totalQty = SaleItem::whereIn('sale_id', $saleIds)
                ->where('product_id', $productId)
                ->sum('quantity');

            foreach ($product->ingredients as $ingredient) {
                $used = (float) $ingredient->pivot->quantity * $totalQty;
                $key = $ingredient->name;

                if (isset($usage[$key])) {
                    $usage[$key]['quantity'] += $used;
                } else {
                    $usage[$key] = [
                        'name' => $ingredient->name,
                        'unit' => $ingredient->unit,
                        'quantity' => $used,
                        'stock' => (float) $ingredient->stock,
                    ];
                }
            }
        }

        // Sort by quantity used
        uasort($usage, fn ($a, $b) => $b['quantity'] <=> $a['quantity']);

        return collect($usage)->take(10);
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

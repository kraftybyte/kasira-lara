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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use UnitEnum;

class SalesReport extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.reports.sales-report';

    protected static ?string $title = 'Laporan Penjualan';

    protected static ?string $slug = 'sales-report';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 1;

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

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $today = ($this->tenant?->now() ?? now())->format('Y-m-d');
        $this->startDate = $today;
        $this->endDate = $today;
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
        return $this->tenant?->dayStart($this->startDate) ?? Carbon::parse($this->startDate)->startOfDay();
    }

    public function getEndDateTime(): Carbon
    {
        return $this->tenant?->dayEnd($this->endDate) ?? Carbon::parse($this->endDate)->endOfDay();
    }

    /**
     * Semua transaksi dalam rentang dan filter aktif. Dipakai oleh tabel (dipaginasi) dan semua angka ringkasan (tanpa paginasi).
     */
    protected function salesQuery(): Builder
    {
        $query = Sale::query()
            ->where('tenant_id', $this->tenant?->id ?? 0)
            ->whereBetween('created_at', [
                $this->getStartDateTime(),
                $this->getEndDateTime(),
            ]);

        // Note: Do NOT filter by closed_at - show ALL sales for historical report

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

        return $query;
    }

    public function getSalesProperty(): Paginator
    {
        return $this->salesQuery()
            ->with(['customer', 'user', 'table', 'payments'])
            ->orderByDesc('created_at')
            ->paginate(100);
    }

    /**
     * Angka ringkasan dari seluruh transaksi yang cocok, dalam satu query.
     */
    public function getTotalsProperty(): object
    {
        return $this->salesQuery()
            ->toBase()
            ->selectRaw('COUNT(*) AS sales_count, COALESCE(SUM(grand_total), 0) AS revenue, COALESCE(SUM(tax), 0) AS tax, COALESCE(SUM(discount), 0) AS discount, COALESCE(SUM(subtotal), 0) AS subtotal')
            ->first();
    }

    public function getTotalSalesProperty(): int
    {
        return (int) $this->totals->sales_count;
    }

    public function getTotalRevenueProperty(): float
    {
        return (float) $this->totals->revenue;
    }

    public function getTotalTaxProperty(): float
    {
        return (float) $this->totals->tax;
    }

    public function getTotalDiscountProperty(): float
    {
        return (float) $this->totals->discount;
    }

    public function getTotalSubtotalProperty(): float
    {
        return (float) $this->totals->subtotal;
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
        if ($this->totalSales === 0) {
            return 0;
        }

        $result = DB::table('sale_items as si')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereIn('si.sale_id', $this->salesQuery()->select('id'))
            ->selectRaw('SUM(p.cost_price * si.quantity) as total_cost')
            ->first();

        $totalCost = (float) ($result->total_cost ?? 0);

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
        $byMethod = $this->salesQuery()
            ->toBase()
            ->selectRaw('payment_method, COUNT(*) AS sales_count, COALESCE(SUM(grand_total), 0) AS amount')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $labels = [
            'cash' => ['Tunai', 'banknotes', 'emerald'],
            'qris' => ['QRIS Auto', 'qr-code', 'blue'],
            'qris_manual' => ['QRIS Manual', 'qr-code', 'purple'],
            'va' => ['Virtual Account', 'building-office', 'indigo'],
            'transfer' => ['Transfer', 'building-library', 'violet'],
            'transfer_manual' => ['Transfer Manual', 'building-library', 'pink'],
            'edc' => ['EDC', 'credit-card', 'purple'],
        ];

        $result = [];
        foreach ($labels as $method => [$label, $icon, $color]) {
            $result[$method] = [
                'label' => $label,
                'icon' => $icon,
                'color' => $color,
                'count' => (int) ($byMethod[$method]->sales_count ?? 0),
                'amount' => (float) ($byMethod[$method]->amount ?? 0),
            ];
        }

        return $result;
    }

    public function getTopProductsProperty(): Collection
    {
        return SaleItem::query()
            ->whereIn('sale_id', $this->salesQuery()->select('id'))
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
        if (! $this->tenant) {
            return collect();
        }

        // Jumlah terjual per produk dalam satu query, lalu resep produk-produk itu dalam satu query lagi.
        $qtyByProduct = SaleItem::query()
            ->whereIn('sale_id', $this->salesQuery()->select('id'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) AS qty')
            ->pluck('qty', 'product_id');

        if ($qtyByProduct->isEmpty()) {
            return collect();
        }

        $usage = [];
        $products = Product::with('ingredients')->whereIn('id', $qtyByProduct->keys())->get();

        foreach ($products as $product) {
            $totalQty = (float) $qtyByProduct[$product->id];

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

    public function updated(string $property): void
    {
        if (in_array($property, ['startDate', 'endDate', 'statusFilter', 'paymentMethodFilter', 'tableSearch'], true)) {
            $this->resetPage();
        }
    }

    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
        $now = $this->tenant?->now() ?? now();
        $this->resetPage();

        switch ($range) {
            case 'today':
                $this->startDate = $now->startOfDay()->format('Y-m-d');
                $this->endDate = $now->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = $now->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->subDay()->endOfDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = $now->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = $now->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->endOfMonth()->format('Y-m-d');
                break;
        }
    }
}

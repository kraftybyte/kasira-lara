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
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use UnitEnum;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.reports.sales-report';

    protected static ?string $title = 'Laporan Penjualan';

    protected static ?string $slug = 'sales-report';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    public string $dateRange = 'today';

    public string $startDate = '';

    public string $endDate = '';

    public ?int $statusFilter = null;

    public ?int $paymentMethodFilter = null;

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

    public function getSalesProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        $query = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [
                $this->getStartDateTime(),
                $this->getEndDateTime(),
            ])
            ->where('status', 'completed');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentMethodFilter) {
            $query->where('payment_method', $this->paymentMethodFilter);
        }

        return $query->with(['customer', 'user', 'table'])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();
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

    public function getSalesByPaymentMethodProperty(): array
    {
        return [
            'cash' => [
                'label' => 'Tunai',
                'count' => $this->sales->where('payment_method', 'cash')->count(),
                'amount' => $this->sales->where('payment_method', 'cash')->sum('grand_total'),
            ],
            'qris' => [
                'label' => 'QRIS',
                'count' => $this->sales->where('payment_method', 'qris')->count(),
                'amount' => $this->sales->where('payment_method', 'qris')->sum('grand_total'),
            ],
            'transfer' => [
                'label' => 'Transfer',
                'count' => $this->sales->where('payment_method', 'transfer')->count(),
                'amount' => $this->sales->where('payment_method', 'transfer')->sum('grand_total'),
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

    public function exportToCsv(): Response
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return response()->make('', 400);
        }

        $sales = $this->sales;

        $filename = 'laporan-penjualan-'.$tenant->name.'-'.now()->format('Y-m-d-His').'.csv';

        $handle = fopen('php://temp', 'r+');

        // Header
        fputcsv($handle, [
            'Invoice',
            'Tanggal',
            'Kasir',
            'Customer',
            'Meja',
            'Subtotal',
            'Diskon',
            'PPN',
            'Total',
            'Metode Bayar',
            'Dibayar',
            'Kembalian',
        ]);

        foreach ($sales as $sale) {
            fputcsv($handle, [
                $sale->invoice_number,
                $sale->created_at->format('d/m/Y H:i'),
                $sale->user?->name ?? '-',
                $sale->customer?->name ?? 'Walk-in',
                $sale->table?->name ?? '-',
                number_format((float) $sale->subtotal, 0, ',', '.'),
                number_format((float) $sale->discount, 0, ',', '.'),
                number_format((float) $sale->tax, 0, ',', '.'),
                number_format((float) $sale->grand_total, 0, ',', '.'),
                $sale->payment_method,
                number_format((float) $sale->paid_amount, 0, ',', '.'),
                number_format((float) $sale->change_amount, 0, ',', '.'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 1;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedHome;

    public int $days = 30;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // All roles can access Dashboard
        return $user->hasAnyRole(['super_admin', 'owner', 'kepala_toko', 'cashier']);
    }

    protected function getStartDate(): Carbon
    {
        return Carbon::now()->subDays($this->days)->startOfDay();
    }

    protected function getEndDate(): Carbon
    {
        return Carbon::now()->endOfDay();
    }

    public function getTenantProperty(): ?Tenant
    {
        return Filament::getTenant();
    }

    public function getUserName(): string
    {
        return Auth::user()?->name ?? 'User';
    }

    public function getSalesDataProperty(): array
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return [];
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Daily sales for chart (include all completed orders regardless of closed_at)
        $dailySales = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days
        $salesByDay = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $salesByDay[$dateKey] = [
                'date' => $current->format('d M'),
                'count' => 0,
                'total' => 0,
            ];
            $current->addDay();
        }

        foreach ($dailySales as $sale) {
            $dateKey = Carbon::parse($sale->date)->format('Y-m-d');
            if (isset($salesByDay[$dateKey])) {
                $salesByDay[$dateKey]['count'] = (int) $sale->count;
                $salesByDay[$dateKey]['total'] = (float) $sale->total;
            }
        }

        return array_values($salesByDay);
    }

    public function getTopProductsProperty(): Collection
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return collect();
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        return SaleItem::query()
            ->whereHas('sale', function ($query) use ($tenant, $startDate, $endDate) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total) as total_sales')
            )
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();
    }

    public function getPaymentSummaryProperty(): array
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return [
                'cash' => 0,
                'qris' => 0,
                'transfer' => 0,
                'total' => 0,
            ];
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $summary = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('payment_method')
            ->get()
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'cash' => (float) ($summary['cash'] ?? 0),
            'qris' => (float) ($summary['qris'] ?? 0),
            'transfer' => (float) ($summary['transfer'] ?? 0),
            'total' => array_sum($summary),
        ];
    }

    public function getTotalSales30DaysProperty(): float
    {
        return $this->paymentSummary['total'];
    }

    public function getTotalTransactions30DaysProperty(): int
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return 0;
        }

        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()])
            ->count();
    }

    public function getAverageTransaction30DaysProperty(): float
    {
        $total = $this->totalSales30Days;
        $count = $this->totalTransactions30Days;

        return $count > 0 ? $total / $count : 0;
    }
}

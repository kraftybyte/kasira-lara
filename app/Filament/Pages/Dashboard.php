<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use BackedEnum;
use Carbon\CarbonInterface;
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

    protected function getStartDate(): CarbonInterface
    {
        return ($this->tenant?->now() ?? now())->subDays($this->days)->startOfDay()->setTimezone(config('app.timezone'));
    }

    protected function getEndDate(): CarbonInterface
    {
        return ($this->tenant?->now() ?? now())->endOfDay()->setTimezone(config('app.timezone'));
    }

    public function getTenantProperty(): ?Tenant
    {
        return Filament::getTenant();
    }

    public function getUserName(): string
    {
        return Auth::user()?->name ?? 'User';
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

    public function getTotalItemsSoldProperty(): float
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return 0;
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        return (float) SaleItem::query()
            ->whereHas('sale', function ($query) use ($tenant, $startDate, $endDate) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('quantity');
    }

    public function getPaymentSummaryProperty(): array
    {
        $tenant = $this->tenant;

        if (! $tenant) {
            return [
                'cash' => 0,
                'qris' => 0,
                'transfer' => 0,
                'edc' => 0,
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
            'edc' => (float) ($summary['edc'] ?? 0),
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

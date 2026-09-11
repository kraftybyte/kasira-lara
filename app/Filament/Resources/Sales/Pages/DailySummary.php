<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;

class DailySummary extends ListRecords
{
    protected static string $resource = \App\Filament\Resources\Sales\SaleResource::class;

    protected static ?string $title = 'Laporan Kas Harian';

    protected static ?string $slug = 'cash-report';

    public ?string $summaryDate = null;

    public function mount(): void
    {
        parent::mount();
        $this->summaryDate = now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->url(SaleResource::getUrl('index'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getSummaryData(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $tenantId = (int) $tenant->getKey();
        $date = $this->summaryDate ?? now()->format('Y-m-d');

        $startOfDay = $date.' 00:00:00';
        $endOfDay = $date.' 23:59:59';

        $sales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where('status', 'completed')
            ->get();

        $cashTotal = 0;
        $qrisTotal = 0;
        $vaTotal = 0;
        $otherTotal = 0;
        $totalSales = 0;
        $totalTransactions = $sales->count();

        foreach ($sales as $sale) {
            $totalSales += $sale->grand_total;

            match ($sale->payment_method) {
                'cash' => $cashTotal += $sale->paid_amount,
                'qris' => $qrisTotal += $sale->grand_total,
                'va' => $vaTotal += $sale->grand_total,
                'transfer' => $otherTotal += $sale->grand_total,
                default => $otherTotal += $sale->grand_total,
            };
        }

        return [
            'date' => $date,
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'cash_total' => $cashTotal,
            'qris_total' => $qrisTotal,
            'va_total' => $vaTotal,
            'other_total' => $otherTotal,
            'cash_in_drawer' => $cashTotal,
            'average_transaction' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
        ];
    }

    public function getRecentSales(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        $tenantId = (int) $tenant->getKey();
        $date = $this->summaryDate ?? now()->format('Y-m-d');

        return Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->with(['customer', 'table'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }
}

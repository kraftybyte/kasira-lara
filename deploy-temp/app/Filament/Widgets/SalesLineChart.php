<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesLineChart extends ChartWidget
{
    protected ?string $heading = 'Penjualan 30 Hari';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dailySales = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // Generate all 30 days with labels
        $labels = [];
        $data = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->format('d M');
            $data[] = (float) ($dailySales[$dateKey] ?? 0);
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $data,
                    'fill' => true,
                    'tension' => 0.4,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => '#ef4444',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

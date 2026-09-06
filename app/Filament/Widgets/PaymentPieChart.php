<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentPieChart extends ChartWidget
{
    protected ?string $heading = 'Metode Pembayaran';

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
            ->pluck('total', 'payment_method');

        $labels = [];
        $data = [];
        $backgroundColors = [];

        $methodColors = [
            'cash' => ['#10b981', 'rgba(16, 185, 129, 0.8)'],
            'qris' => ['#3b82f6', 'rgba(59, 130, 246, 0.8)'],
            'transfer' => ['#8b5cf6', 'rgba(139, 92, 246, 0.8)'],
        ];

        foreach (['cash', 'qris', 'transfer'] as $method) {
            $value = (float) ($summary[$method] ?? 0);
            if ($value > 0) {
                $labels[] = ucfirst($method);
                $data[] = $value;
                $colors = $methodColors[$method] ?? ['#6b7280', 'rgba(107, 114, 128, 0.8)'];
                $backgroundColors[] = $colors[0];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pembayaran',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 0,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

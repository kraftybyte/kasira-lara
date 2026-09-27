<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Pages\Reports\SalesReport;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('salesReport')
                ->label('Laporan')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(SalesReport::getUrl())
                ->visible(function () {
                    return auth()->user()->can('DeleteAny:Sale');
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['items', 'table', 'customer', 'user']);
    }
}

<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Pages\Reports\IngredientReport;
use App\Filament\Resources\Ingredients\IngredientResource;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;

class ListIngredients extends ListRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ingredientReport')
                ->label('Laporan')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(IngredientReport::getUrl()),
            CreateAction::make()
                ->label('Tambah Bahan')
                ->slideOver(false)
                ->modalHeading('Tambah Bahan Baru')
                ->modalWidth('md')
                ->form([
                    TextInput::make('name')
                        ->label('Nama Bahan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sku')
                        ->label('SKU')
                        ->maxLength(255),
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            if (! $tenant) {
                                return [];
                            }

                            return Supplier::query()
                                ->where('tenant_id', $tenant->id)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    TextInput::make('unit')
                        ->label('Satuan')
                        ->default('pcs')
                        ->required()
                        ->maxLength(20),
                    TextInput::make('stock')
                        ->label('Stok')
                        ->numeric()
                        ->default(0),
                    TextInput::make('cost_price')
                        ->label('Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->createAnother(false),
        ];
    }
}

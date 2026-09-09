<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Bahan')
                    ->required()
                    ->maxLength(255),

                TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(255)
                    ->nullable(),

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
                    ->nullable()
                    ->searchable()
                    ->preload(),

                TextInput::make('unit')
                    ->label('Satuan')
                    ->default('pcs')
                    ->required()
                    ->maxLength(20),

                TextInput::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                TextInput::make('minimum_stock')
                    ->label('Stok Minimum')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Trigger alert saat stok di bawah ini'),

                TextInput::make('reorder_point')
                    ->label('Titik Reorder')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Trigger reorder otomatis'),

                Toggle::make('auto_reorder')
                    ->label('Auto Reorder')
                    ->default(false)
                    ->helperText('Aktifkan untuk sugesti reorder otomatis'),

                TextInput::make('cost_price')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}

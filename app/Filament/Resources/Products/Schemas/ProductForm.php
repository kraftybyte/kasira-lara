<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Ingredient;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('category_id')
                    ->label('Category')
                    ->options(function () {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return [];
                        }

                        return Category::query()
                            ->where('tenant_id', $tenant->id)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255),

                TextInput::make('barcode')
                    ->label('Barcode')
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('name')
                    ->label('Product Name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->label('Product Image')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->imageEditor()
                    ->imageResizeTargetWidth(600)
                    ->imageResizeTargetHeight(600)
                    ->columnSpanFull(),

                TextInput::make('cost_price')
                    ->label('Cost Price')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                TextInput::make('selling_price')
                    ->label('Selling Price')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                Select::make('rate_type')
                    ->label('Rate Type')
                    ->options([
                        'fixed' => 'Fixed (per item)',
                        'duration' => 'Duration (per hour)',
                    ])
                    ->default('fixed')
                    ->live()
                    ->required(),

                TextInput::make('rate')
                    ->label('Rate (per hour)')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->hidden(fn (callable $get) => $get('rate_type') !== 'duration')
                    ->required(fn (callable $get) => $get('rate_type') === 'duration'),

                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->hidden(fn (callable $get) => $get('rate_type') === 'duration'),

                TextInput::make('minimum_stock')
                    ->label('Minimum Stock')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->hidden(fn (callable $get) => $get('rate_type') === 'duration'),

                TextInput::make('unit')
                    ->label('Unit')
                    ->default('pcs')
                    ->maxLength(50)
                    ->hidden(fn (callable $get) => $get('rate_type') === 'duration'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                /*
                |--------------------------------------------------------------------------
                | ADDITIONAL OPTIONS (Tambahan/Extra)
                |--------------------------------------------------------------------------
                */
                Repeater::make('modifiers')
                    ->label('Tambahan / Extra')
                    ->relationship()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('Contoh: Telur, Nasi, Extra Keju'),

                        TextInput::make('price_adjustment')
                            ->label('Harga Tambahan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),

                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'addon' => 'Extra (Tambah Harga)',
                                'option' => 'Pilihan (Tidak Tambah)',
                            ])
                            ->default('addon')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->default([])
                    ->addActionLabel('Tambah Tambahan'),

                /*
                |--------------------------------------------------------------------------
                | INGREDIENTS (Komposisi Bahan)
                |--------------------------------------------------------------------------
                */
                Repeater::make('ingredients')
                    ->label('Komposisi Bahan')
                    ->relationship()
                    ->schema([
                        Select::make('ingredient_id')
                            ->label('Bahan')
                            ->options(function () {
                                $tenant = Filament::getTenant();

                                if (! $tenant) {
                                    return [];
                                }

                                return Ingredient::query()
                                    ->where('tenant_id', $tenant->id)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->distinct(),

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->default(1)
                            ->minValue(0.001)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->default([])
                    ->addActionLabel('Tambah Bahan'),

            ]);
    }
}

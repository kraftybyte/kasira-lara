<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('categories')
                ->label('Categories')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(CategoryResource::getUrl('index')),
            CreateAction::make()
                ->label('New Product')
                ->slideOver(false)
                ->modalHeading('Tambah Produk Baru')
                ->modalWidth('lg')
                ->form([
                    Select::make('category_id')
                        ->label('Kategori')
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
                    TextInput::make('name')
                        ->label('Nama Produk')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('selling_price')
                        ->label('Harga Jual')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    TextInput::make('stock')
                        ->label('Stok')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->createAnother(false),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}

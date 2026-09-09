<?php

namespace App\Filament\Resources\Ingredients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge(),

                TextColumn::make('minimum_stock')
                    ->label('Min. Stok')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->placeholder('-'),

                IconColumn::make('needs_reorder')
                    ->label('Reorder')
                    ->getStateUsing(fn ($record) => $record->needsReorder())
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),

                TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

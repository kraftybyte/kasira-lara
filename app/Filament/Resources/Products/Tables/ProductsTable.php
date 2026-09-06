<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->square()
                    ->size(40),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('rate_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fixed' => 'Fixed',
                        'duration' => 'Duration',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'gray',
                        'duration' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('ingredients')
                    ->label('Bahan')
                    ->getStateUsing(function ($record) {
                        $count = $record->ingredients()->count();

                        return $count > 0
                            ? "{$count} bahan"
                            : '-';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->ingredients()->count() > 0 ? 'info' : 'gray'),

                TextColumn::make('selling_price')
                    ->label('Price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state): string => (int) $state)
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

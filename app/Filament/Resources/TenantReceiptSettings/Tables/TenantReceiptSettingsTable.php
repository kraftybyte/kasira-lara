<?php

namespace App\Filament\Resources\TenantReceiptSettings\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantReceiptSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store_name')
                    ->label('Nama Toko')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Telepon'),

                TextColumn::make('paper_size')
                    ->label('Kertas')
                    ->badge(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Tables\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Meja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('table_number')
                    ->label('Nomor')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'available',
                        'danger' => 'active',
                        'warning' => 'reserved',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'available',
                        'heroicon-o-user-group' => 'active',
                        'heroicon-o-calendar' => 'reserved',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'active' => 'Digunakan',
                        'reserved' => 'Dipesan',
                        default => $state,
                    }),

                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->formatStateUsing(fn (int $state): string => "{$state} org"),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}

<?php

namespace App\Filament\Resources\Tables\Tables;

use App\Models\Table as TableModel;
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

                BadgeColumn::make('display_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Tersedia',
                        'danger' => 'Digunakan',
                        'warning' => 'Dipesan',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Tersedia',
                        'heroicon-o-user-group' => 'Digunakan',
                        'heroicon-o-calendar' => 'Dipesan',
                    ])
                    ->formatStateUsing(fn (TableModel $record): string => $record->getDisplayStatus()),

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

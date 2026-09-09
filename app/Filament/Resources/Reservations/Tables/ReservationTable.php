<?php

namespace App\Filament\Resources\Reservations;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('reservation_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('reservation_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('guest_count')
                    ->label('Tamu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('table.name')
                    ->label('Meja')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'seated' => 'Ditempatkan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->colors(fn (string $state): array => match ($state) {
                        'pending' => ['warning'],
                        'confirmed' => ['info'],
                        'seated' => ['success'],
                        'completed' => ['gray'],
                        'cancelled' => ['danger'],
                        default => ['gray'],
                    }),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'seated' => 'Ditempatkan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),

                SelectFilter::make('reservation_date')
                    ->label('Tanggal')
                    ->options(function () {
                        $dates = collect();
                        for ($i = 0; $i < 7; $i++) {
                            $date = now()->addDays($i);
                            $dates[$date->format('Y-m-d')] = $date->format('d M Y');
                        }

                        return $dates->toArray();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reservation_date', 'desc');
    }
}

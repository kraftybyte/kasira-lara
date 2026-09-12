<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                Reservation::query()->with(['table', 'customer'])
            )
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_phone')
                    ->label('No. HP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('table.name')
                    ->label('Meja')
                    ->sortable(),

                TextColumn::make('guest_count')
                    ->label('Jumlah Tamu')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('reservation_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('reservation_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'seated',
                        'danger' => 'cancelled',
                    ]),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'seated' => 'Seated',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('reservation_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query) => $query->where('reservation_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($query) => $query->where('reservation_date', '<=', $data['until'])
                            );
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reservation_date', 'desc');
    }
}

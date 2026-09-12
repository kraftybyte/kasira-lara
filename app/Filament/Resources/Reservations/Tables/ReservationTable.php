<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Models\Reservation;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
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
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('No. HP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('table.name')
                    ->label('Meja')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest_count')
                    ->label('Jumlah Tamu')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reservation_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reservation_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'seated',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'seated' => 'Seated',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('reservation_date')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reservation_date', 'desc');
    }
}

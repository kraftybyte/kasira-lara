<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                /*
                |--------------------------------------------------------------------------
                | INVOICE
                |--------------------------------------------------------------------------
                */

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                /*
                |--------------------------------------------------------------------------
                | CUSTOMER
                |--------------------------------------------------------------------------
                */

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->placeholder('Walk-in Customer')
                    ->searchable(),

                /*
                |--------------------------------------------------------------------------
                | KASIR
                |--------------------------------------------------------------------------
                */

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->placeholder('-')
                    ->searchable(),

                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                */

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                TextColumn::make('payments.method')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(function ($state) {

                        return match (strtolower((string) $state)) {

                            'cash' => 'Tunai',

                            'qris' => 'QRIS',

                            'transfer' => 'Transfer',

                            'bank_transfer' => 'Transfer',

                            'debit' => 'Debit',

                            'credit' => 'Credit Card',

                            'credit_card' => 'Credit Card',

                            default => $state
                                ? ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $state
                                    )
                                )
                                : '-',

                        };

                    }),

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {

                            'completed' => 'success',

                            'pending' => 'warning',

                            'cancelled' => 'danger',

                            default => 'gray',

                        }
                    )
                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'completed' => 'Selesai',

                            'pending' => 'Pending',

                            'cancelled' => 'Dibatalkan',

                            default => ucfirst($state),

                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | TANGGAL
                |--------------------------------------------------------------------------
                */

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

            ])

            /*
            |--------------------------------------------------------------------------
            | FILTER
            |--------------------------------------------------------------------------
            */

            ->filters([

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([

                        'completed' => 'Selesai',

                        'pending' => 'Pending',

                        'cancelled' => 'Dibatalkan',

                    ]),

            ])

            /*
            |--------------------------------------------------------------------------
            | ACTIONS
            |--------------------------------------------------------------------------
            */

            ->recordActions([

                /*
                |--------------------------------------------------------------------------
                | VIEW
                |--------------------------------------------------------------------------
                */

                ViewAction::make(),

                /*
                |--------------------------------------------------------------------------
                | EDIT
                |--------------------------------------------------------------------------
                */

                EditAction::make(),

                /*
                |--------------------------------------------------------------------------
                | CETAK STRUK
                |--------------------------------------------------------------------------
                */

                Action::make('printReceipt')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(function ($record) {

                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return '#';
                        }

                        return route(
                            'receipt.show',
                            [
                                'tenant' => $tenant->getRouteKey(),
                                'sale' => $record->getRouteKey(),
                            ]
                        ).'?size=80mm';
                    })
                    ->openUrlInNewTab(),

            ])

            /*
            |--------------------------------------------------------------------------
            | BULK ACTIONS
            |--------------------------------------------------------------------------
            */

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ]);
    }
}

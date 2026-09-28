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
                | TABLE
                |--------------------------------------------------------------------------
                */

                TextColumn::make('table_display')
                    ->label('Meja')
                    ->getStateUsing(function ($record) {
                        if ($record->table_id && $record->table) {
                            return $record->table->name;
                        }

                        return 'Takeaway';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Takeaway' ? 'gray' : 'info'),

                /*
                |--------------------------------------------------------------------------
                | ITEMS (Products)
                |--------------------------------------------------------------------------
                */

                TextColumn::make('items_summary')
                    ->label('Produk')
                    ->getStateUsing(function ($record) {
                        $items = $record->items->take(3);
                        $names = $items->pluck('product_name')->toArray();
                        $more = $record->items->count() - 3;

                        $text = implode(', ', $names);
                        if ($more > 0) {
                            $text .= " +{$more} more";
                        }

                        return $text ?: '-';
                    })
                    ->tooltip(function ($record) {
                        $items = $record->items->map(function ($item) {
                            return "{$item->quantity}x {$item->product_name}";
                        })->toArray();

                        return implode("\n", $items);
                    })
                    ->lineClamp(1),

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
                    ->color(
                        function ($state) {
                            return match (strtolower((string) $state)) {
                                'cash' => 'success',
                                'qris' => 'info',
                                'qris_manual' => 'warning',
                                'va', 'virtual_account' => 'primary',
                                'transfer', 'bank_transfer' => 'gray',
                                default => 'gray',
                            };
                        }
                    )
                    ->formatStateUsing(function ($state) {

                        return match (strtolower((string) $state)) {

                            'cash' => '💵 Tunai',

                            'qris' => '📱 QRIS',

                            'qris_manual' => '📷 QR Manual',

                            'transfer' => '🏦 Transfer',

                            'bank_transfer' => '🏦 Transfer',

                            'debit' => '💳 Debit',

                            'credit' => '💳 Credit Card',

                            'credit_card' => '💳 Credit Card',

                            'va', 'virtual_account' => '🏧 VA',

                            default => $state
                                ? '💰 '.ucfirst(
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
            | DEFAULT SORT
            |--------------------------------------------------------------------------
            */

            ->defaultSort('created_at', 'desc')

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

                SelectFilter::make('payment_method')
                    ->label('Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'qris' => 'QRIS',
                        'va' => 'Virtual Account',
                    ]),

                SelectFilter::make('order_type')
                    ->label('Tipe Order')
                    ->options([
                        'table' => 'Meja',
                        'takeaway' => 'Takeaway',
                    ])
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            if ($data['value'] === 'table') {
                                return $query->whereNotNull('table_id');
                            } else {
                                return $query->whereNull('table_id');
                            }
                        }

                        return $query;
                    }),

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

                EditAction::make()
                    ->visible(function () {
                        return auth()->user()->can('Update:Sale');
                    }),

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

                    DeleteBulkAction::make()
                        ->visible(function () {
                            return auth()->user()->can('DeleteAny:Sale');
                        }),

                ]),

            ]);
    }
}

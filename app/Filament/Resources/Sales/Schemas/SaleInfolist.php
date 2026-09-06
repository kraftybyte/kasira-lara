<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | INFORMASI TRANSAKSI
                |--------------------------------------------------------------------------
                */

                Section::make('Informasi Transaksi')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([

                        TextEntry::make('invoice_number')
                            ->label('Invoice')
                            ->copyable()
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(
                                fn ($state) => match ($state) {
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
                                    default => ucfirst((string) $state),
                                }
                            ),

                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->placeholder('Walk-in Customer'),

                        TextEntry::make('user.name')
                            ->label('Kasir')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Tanggal Transaksi')
                            ->dateTime('d F Y H:i'),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------------------
                | ITEM TRANSAKSI
                |--------------------------------------------------------------------------
                */

                Section::make('Item Transaksi')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([

                        TextEntry::make('items')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state, $record) {

                                if (! $record->items || $record->items->isEmpty()) {
                                    return '
                                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                            Tidak ada item transaksi.
                                        </div>
                                    ';
                                }

                                $rows = '';

                                foreach ($record->items as $item) {

                                    $quantity = number_format(
                                        (float) $item->quantity,
                                        3,
                                        ',',
                                        '.'
                                    );

                                    $unitPrice = number_format(
                                        (float) $item->unit_price,
                                        0,
                                        ',',
                                        '.'
                                    );

                                    $total = number_format(
                                        (float) $item->total,
                                        0,
                                        ',',
                                        '.'
                                    );

                                    $rows .= "
                                        <tr class=\"border-b border-gray-100 dark:border-gray-800\">
                                            <td class=\"px-4 py-3\">
                                                <div class=\"font-medium text-gray-950 dark:text-white\">
                                                    {$item->product_name}
                                                </div>

                                                <div class=\"mt-0.5 text-xs text-gray-500\">
                                                    ".e($item->sku ?? '-')."
                                                </div>
                                            </td>

                                            <td class=\"px-4 py-3 text-center\">
                                                {$quantity}
                                            </td>

                                            <td class=\"px-4 py-3 text-right\">
                                                Rp {$unitPrice}
                                            </td>

                                            <td class=\"px-4 py-3 text-right font-semibold\">
                                                Rp {$total}
                                            </td>
                                        </tr>
                                    ";
                                }

                                return "
                                    <div class=\"overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700\">
                                        <table class=\"w-full text-sm\">
                                            <thead class=\"bg-gray-50 dark:bg-gray-800\">
                                                <tr>
                                                    <th class=\"px-4 py-3 text-left font-semibold\">
                                                        Produk
                                                    </th>

                                                    <th class=\"px-4 py-3 text-center font-semibold\">
                                                        Qty
                                                    </th>

                                                    <th class=\"px-4 py-3 text-right font-semibold\">
                                                        Harga
                                                    </th>

                                                    <th class=\"px-4 py-3 text-right font-semibold\">
                                                        Total
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                {$rows}
                                            </tbody>
                                        </table>
                                    </div>
                                ";
                            }),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | RINGKASAN PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                Section::make('Ringkasan Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([

                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('IDR'),

                        TextEntry::make('discount')
                            ->label('Diskon')
                            ->money('IDR'),

                        TextEntry::make('tax')
                            ->label('PPN')
                            ->money('IDR'),

                        TextEntry::make('grand_total')
                            ->label('Total')
                            ->money('IDR')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('paid_amount')
                            ->label('Dibayar')
                            ->money('IDR'),

                        TextEntry::make('change_amount')
                            ->label('Kembalian')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------------------
                | METODE PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                Section::make('Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->schema([

                        TextEntry::make('payments')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state, $record) {

                                if (! $record->payments || $record->payments->isEmpty()) {
                                    return '
                                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                            Tidak ada data pembayaran.
                                        </div>
                                    ';
                                }

                                $rows = '';

                                foreach ($record->payments as $payment) {

                                    $method = match (strtolower((string) $payment->method)) {

                                        'cash' => 'Tunai',

                                        'qris' => 'QRIS',

                                        'transfer',
                                        'bank_transfer' => 'Transfer',

                                        'debit' => 'Debit',

                                        'credit',
                                        'credit_card' => 'Credit Card',

                                        default => ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string) $payment->method
                                            )
                                        ),
                                    };

                                    $amount = number_format(
                                        (float) $payment->amount,
                                        0,
                                        ',',
                                        '.'
                                    );

                                    $reference = e(
                                        $payment->reference ?: '-'
                                    );

                                    $paidAt = $payment->paid_at
                                        ? $payment->paid_at->format('d M Y H:i')
                                        : '-';

                                    $rows .= "
                                        <tr class=\"border-b border-gray-100 dark:border-gray-800\">

                                            <td class=\"px-4 py-3\">
                                                <span class=\"font-medium text-gray-950 dark:text-white\">
                                                    {$method}
                                                </span>
                                            </td>

                                            <td class=\"px-4 py-3 text-right font-semibold\">
                                                Rp {$amount}
                                            </td>

                                            <td class=\"px-4 py-3 text-right text-gray-500\">
                                                {$reference}
                                            </td>

                                            <td class=\"px-4 py-3 text-right text-gray-500\">
                                                {$paidAt}
                                            </td>

                                        </tr>
                                    ";
                                }

                                return "
                                    <div class=\"overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700\">
                                        <table class=\"w-full text-sm\">

                                            <thead class=\"bg-gray-50 dark:bg-gray-800\">
                                                <tr>

                                                    <th class=\"px-4 py-3 text-left font-semibold\">
                                                        Metode
                                                    </th>

                                                    <th class=\"px-4 py-3 text-right font-semibold\">
                                                        Jumlah
                                                    </th>

                                                    <th class=\"px-4 py-3 text-right font-semibold\">
                                                        Referensi
                                                    </th>

                                                    <th class=\"px-4 py-3 text-right font-semibold\">
                                                        Waktu
                                                    </th>

                                                </tr>
                                            </thead>

                                            <tbody>
                                                {$rows}
                                            </tbody>

                                        </table>
                                    </div>
                                ";
                            }),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | CATATAN
                |--------------------------------------------------------------------------
                */

                Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->schema([

                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Tidak ada catatan.')
                            ->columnSpanFull(),

                    ])
                    ->collapsible(),

            ]);
    }
}

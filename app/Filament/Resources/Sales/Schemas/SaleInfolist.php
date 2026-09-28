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
                                        (int) $item->quantity,
                                        0,
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
                                            <td class=\"px-3 py-2\">
                                                <div class=\"font-medium text-gray-950 dark:text-white text-sm\">
                                                    {$item->product_name}
                                                </div>

                                                <div class=\"text-xs text-gray-500\">
                                                    ".e($item->sku ?? '-')."
                                                </div>
                                            </td>

                                            <td class=\"px-3 py-2 text-center text-sm\">
                                                {$quantity}
                                            </td>

                                            <td class=\"px-3 py-2 text-right text-sm\">
                                                Rp {$unitPrice}
                                            </td>

                                            <td class=\"px-3 py-2 text-right text-sm font-semibold\">
                                                Rp {$total}
                                            </td>
                                        </tr>
                                    ";
                                }

                                return "
                                    <div class=\"rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden\">
                                        <div class=\"overflow-y-auto\" style=\"max-height: 200px;\">
                                            <table class=\"w-full text-sm\">
                                                <thead class=\"bg-gray-50 dark:bg-gray-800 sticky top-0 shadow-sm\">
                                                    <tr>
                                                        <th class=\"px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-400\">
                                                            Produk
                                                        </th>

                                                        <th class=\"px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-400 w-16\">
                                                            Qty
                                                        </th>

                                                        <th class=\"px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-400 w-24\">
                                                            Harga
                                                        </th>

                                                        <th class=\"px-3 py-2 text-right font-semibold text-gray-600 dark:text-gray-400 w-28\">
                                                            Total
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    {$rows}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ";
                            }),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | RINGKASAN & PEMBAYARAN (Combined)
                |--------------------------------------------------------------------------
                */

                Section::make('Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([

                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('IDR')
                            ->columnSpan(1),

                        TextEntry::make('discount')
                            ->label('Diskon')
                            ->money('IDR')
                            ->columnSpan(1),

                        TextEntry::make('tax')
                            ->label('PPN')
                            ->money('IDR')
                            ->columnSpan(1),

                        TextEntry::make('grand_total')
                            ->label('Total')
                            ->money('IDR')
                            ->weight('bold')
                            ->columnSpan(1),

                        TextEntry::make('paid_amount')
                            ->label('Dibayar')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success')
                            ->columnSpan(1),

                        TextEntry::make('change_amount')
                            ->label('Kembalian')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('warning')
                            ->columnSpan(1),

                        TextEntry::make('payment_method_display')
                            ->label('Metode Bayar')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state, $record) {
                                $payments = $record->payments ?? collect();
                                if ($payments->isEmpty()) {
                                    return '<span class="text-gray-400 italic">Belum ada pembayaran</span>';
                                }

                                $rows = '';
                                foreach ($payments as $payment) {
                                    $method = strtolower((string) $payment->method);
                                    $methodLabel = match ($method) {
                                        'cash' => ['icon' => '💵', 'label' => 'Tunai', 'color' => 'success'],
                                        'qris' => ['icon' => '📱', 'label' => 'QRIS', 'color' => 'info'],
                                        'qris_manual' => ['icon' => '📷', 'label' => 'QR Manual', 'color' => 'warning'],
                                        'va', 'virtual_account' => ['icon' => '🏧', 'label' => 'Virtual Account', 'color' => 'primary'],
                                        'transfer', 'bank_transfer' => ['icon' => '🏦', 'label' => 'Transfer Bank', 'color' => 'gray'],
                                        'debit' => ['icon' => '💳', 'label' => 'Debit', 'color' => 'gray'],
                                        'credit', 'credit_card' => ['icon' => '💳', 'label' => 'Credit Card', 'color' => 'gray'],
                                        default => ['icon' => '💰', 'label' => ucfirst(str_replace('_', ' ', $payment->method)), 'color' => 'gray'],
                                    };

                                    $amount = number_format((float) $payment->amount, 0, ',', '.');
                                    $paidAt = $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '-';
                                    $reference = e($payment->reference ?: '-');

                                    $colorClass = match ($methodLabel['color']) {
                                        'success' => 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800',
                                        'info' => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
                                        'primary' => 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800',
                                        'warning' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                                    };

                                    $rows .= "
                                        <div class=\"flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0\">
                                            <span class=\"inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {$colorClass}\">
                                                <span>{$methodLabel['icon']}</span>
                                                <span>{$methodLabel['label']}</span>
                                            </span>
                                            <div class=\"text-right\">
                                                <div class=\"font-bold text-gray-900 dark:text-white\">Rp {$amount}</div>
                                                <div class=\"text-xs text-gray-500\">{$reference}</div>
                                            </div>
                                        </div>
                                    ";
                                }

                                return "<div class=\"space-y-0\">{$rows}</div>";
                            }),

                    ])
                    ->columns(3),

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

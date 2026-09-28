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
                            ->color(fn ($state) => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'completed' => 'Selesai',
                                'pending' => 'Pending',
                                'cancelled' => 'Dibatalkan',
                                default => ucfirst((string) $state),
                            }),

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

                Section::make('Item Transaksi')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        TextEntry::make('items')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state, $record) => self::renderItemsTable($record)),
                    ]),

                Section::make('Pembayaran')
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
                            ->weight('bold'),

                        TextEntry::make('paid_amount')
                            ->label('Dibayar')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),

                        TextEntry::make('change_amount')
                            ->label('Kembalian')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('warning'),

                        TextEntry::make('payment_method_display')
                            ->label('Metode Bayar')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state, $record) => self::renderPaymentMethods($record)),
                    ])
                    ->columns(3),

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

    private static function renderItemsTable($record): string
    {
        if (! $record->items || $record->items->isEmpty()) {
            return '<div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">Tidak ada item.</div>';
        }

        $rows = '';
        foreach ($record->items as $item) {
            $qty = number_format((int) $item->quantity, 0, ',', '.');
            $price = number_format((float) $item->unit_price, 0, ',', '.');
            $total = number_format((float) $item->total, 0, ',', '.');

            $rows .= "
                <tr class=\"border-b border-gray-100 dark:border-gray-800 last:border-0\">
                    <td class=\"px-3 py-2\">
                        <div class=\"font-medium text-sm text-gray-900 dark:text-white\">{$item->product_name}</div>
                        <div class=\"text-xs text-gray-500\">".e($item->sku ?? '-')."</div>
                    </td>
                    <td class=\"px-3 py-2 text-center text-sm\">{$qty}</td>
                    <td class=\"px-3 py-2 text-right text-sm\">Rp {$price}</td>
                    <td class=\"px-3 py-2 text-right text-sm font-semibold\">Rp {$total}</td>
                </tr>
            ";
        }

        return "
            <div class=\"rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden\">
                <div class=\"overflow-y-auto scrollbar-thin\" style=\"max-height: 56px;\">
                    <table class=\"w-full text-sm\">
                        <thead class=\"bg-gray-50 dark:bg-gray-800 sticky top-0 shadow-sm\">
                            <tr>
                                <th class=\"px-3 py-2 text-left text-xs font-semibold text-gray-500\">Produk</th>
                                <th class=\"px-3 py-2 text-center text-xs font-semibold text-gray-500 w-16\">Qty</th>
                                <th class=\"px-3 py-2 text-right text-xs font-semibold text-gray-500 w-24\">Harga</th>
                                <th class=\"px-3 py-2 text-right text-xs font-semibold text-gray-500 w-28\">Total</th>
                            </tr>
                        </thead>
                        <tbody>{$rows}</tbody>
                    </table>
                </div>
            </div>
        ";
    }

    private static function renderPaymentMethods($record): string
    {
        $payments = $record->payments ?? collect();
        if ($payments->isEmpty()) {
            return '<span class="text-gray-400 italic">Belum ada pembayaran</span>';
        }

        $rows = '';
        foreach ($payments as $payment) {
            $method = strtolower((string) $payment->method);
            $label = match ($method) {
                'cash' => ['icon' => '💵', 'label' => 'Tunai', 'color' => 'success'],
                'qris' => ['icon' => '📱', 'label' => 'QRIS', 'color' => 'info'],
                'qris_manual' => ['icon' => '📷', 'label' => 'QR Manual', 'color' => 'warning'],
                'va', 'virtual_account' => ['icon' => '🏧', 'label' => 'Virtual Account', 'color' => 'primary'],
                'transfer', 'bank_transfer' => ['icon' => '🏦', 'label' => 'Transfer Bank', 'color' => 'gray'],
                'debit', 'credit', 'credit_card' => ['icon' => '💳', 'label' => ucfirst(str_replace('_', ' ', $method)), 'color' => 'gray'],
                default => ['icon' => '💰', 'label' => ucfirst(str_replace('_', ' ', $method)), 'color' => 'gray'],
            };

            $amount = number_format((float) $payment->amount, 0, ',', '.');
            $reference = e($payment->reference ?: '-');

            $color = match ($label['color']) {
                'success' => 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400',
                'info' => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
                'primary' => 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400',
                'warning' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400',
                default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300',
            };

            $rows .= "
                <div class=\"flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0\">
                    <span class=\"inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-semibold {$color}\">
                        <span>{$label['icon']}</span>
                        <span>{$label['label']}</span>
                    </span>
                    <div class=\"text-right\">
                        <div class=\"font-bold text-gray-900 dark:text-white\">Rp {$amount}</div>
                        <div class=\"text-xs text-gray-500\">{$reference}</div>
                    </div>
                </div>
            ";
        }

        return "<div class=\"space-y-0\">{$rows}</div>";
    }
}

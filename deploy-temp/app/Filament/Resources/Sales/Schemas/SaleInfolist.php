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
                            ->placeholder('Walk-in'),

                        TextEntry::make('user.name')
                            ->label('Kasir')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime('d M Y, H:i'),
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

                        TextEntry::make('tax')
                            ->label('PPN')
                            ->money('IDR'),

                        TextEntry::make('discount')
                            ->label('Diskon')
                            ->money('IDR'),

                        TextEntry::make('grand_total')
                            ->label('Total')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('danger'),

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
                            ->label('Metode')
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
            return '<div class="text-center text-sm text-gray-400 py-4">Tidak ada item</div>';
        }

        $rows = '';
        $itemCount = $record->items->count();
        $maxHeight = $itemCount > 4 ? '140px' : 'auto';

        foreach ($record->items as $item) {
            $qty = number_format((int) $item->quantity, 0, ',', '.');
            $price = number_format((float) $item->unit_price, 0, ',', '.');
            $total = number_format((float) $item->total, 0, ',', '.');

            $rows .= "
                <tr class=\"group hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors\">
                    <td class=\"px-3 py-2\">
                        <div class=\"font-medium text-sm text-gray-900 dark:text-white\">{$item->product_name}</div>
                    </td>
                    <td class=\"px-3 py-2 text-center text-sm text-gray-600 dark:text-gray-400\">{$qty}</td>
                    <td class=\"px-3 py-2 text-right text-sm text-gray-600 dark:text-gray-400\">Rp {$price}</td>
                    <td class=\"px-3 py-2 text-right text-sm font-semibold text-gray-900 dark:text-white\">Rp {$total}</td>
                </tr>
            ";
        }

        return "
            <div class=\"rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden\">
                <div class=\"overflow-y-auto\" style=\"max-height: {$maxHeight};\">
                    <table class=\"w-full text-sm\">
                        <thead>
                            <tr class=\"bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700\">
                                <th class=\"px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400\">Produk</th>
                                <th class=\"px-3 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 w-14\">Qty</th>
                                <th class=\"px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 w-24\">Harga</th>
                                <th class=\"px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 w-28\">Total</th>
                            </tr>
                        </thead>
                        <tbody class=\"divide-y divide-gray-100 dark:divide-gray-800\">{$rows}</tbody>
                    </table>
                </div>
            </div>
        ";
    }

    private static function renderPaymentMethods($record): string
    {
        $payments = $record->payments ?? collect();
        if ($payments->isEmpty()) {
            return '<span class="text-sm text-gray-400 italic">Belum ada pembayaran</span>';
        }

        $items = [];
        foreach ($payments as $payment) {
            $method = strtolower((string) $payment->method);
            $label = match ($method) {
                'cash' => ['icon' => '💵', 'label' => 'Tunai'],
                'qris' => ['icon' => '📱', 'label' => 'QRIS'],
                'qris_manual' => ['icon' => '📷', 'label' => 'QR Manual'],
                'va', 'virtual_account' => ['icon' => '🏧', 'label' => 'VA'],
                'transfer', 'bank_transfer' => ['icon' => '🏦', 'label' => 'Transfer'],
                'debit' => ['icon' => '💳', 'label' => 'Debit'],
                'credit', 'credit_card' => ['icon' => '💳', 'label' => 'Credit'],
                'counter' => ['icon' => '🏪', 'label' => 'Counter'],
                default => ['icon' => '💰', 'label' => ucfirst($method)],
            };

            $amount = number_format((float) $payment->amount, 0, ',', '.');
            $reference = e($payment->reference ?: '-');

            $items[] = "
                <div class=\"flex items-center justify-between py-2\">
                    <span class=\"inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300\">
                        <span>{$label['icon']}</span>
                        <span>{$label['label']}</span>
                    </span>
                    <span class=\"font-semibold text-gray-900 dark:text-white\">Rp {$amount}</span>
                </div>
            ";
        }

        return '<div class="space-y-0">'.implode('', $items).'</div>';
    }
}

<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Sale;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Open - Belum Bayar',
                                'pending' => 'Pending - Menunggu Pembayaran',
                                'completed' => 'Completed - Lunas',
                                'cancelled' => 'Cancelled - Dibatalkan',
                            ])
                            ->required(),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => '💵 Tunai',
                                'qris' => '📱 QRIS',
                                'qris_manual' => '📷 QRIS Manual',
                                'transfer' => '🏦 Transfer Bank',
                                'debit' => '💳 Debit',
                                'credit' => '💳 Credit Card',
                                'va' => '🏧 Virtual Account',
                                'counter' => '🏪 Bayar di Kasir',
                            ]),

                        Radio::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'unpaid' => 'Belum Bayar',
                                'paid' => 'Lunas',
                            ]),
                    ]),

                Section::make('Detail Pembayaran')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('tax')
                            ->label('Pajak')
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('grand_total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('paid_amount')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('change_amount')
                            ->label('Kembalian')
                            ->numeric()
                            ->prefix('Rp'),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3),
                    ]),
            ]);
    }
}

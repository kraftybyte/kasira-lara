<?php

namespace App\Filament\Resources\TenantReceiptSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantReceiptSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Toko')
                ->schema([
                    TextInput::make('store_name')
                        ->label('Nama Toko')
                        ->maxLength(255),

                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3),

                    TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Logo Struk')
                ->schema([
                    Toggle::make('show_logo')
                        ->label('Tampilkan Logo')
                        ->default(true)
                        ->live(),

                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->directory('tenant-receipts')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->visible(fn ($get): bool => (bool) $get('show_logo')),
                ]),

            Section::make('Informasi Struk')
                ->schema([
                    Toggle::make('show_address')
                        ->label('Tampilkan Alamat')
                        ->default(true),

                    Toggle::make('show_phone')
                        ->label('Tampilkan Nomor Telepon')
                        ->default(true),

                    Toggle::make('show_customer')
                        ->label('Tampilkan Customer')
                        ->default(true),

                    Toggle::make('show_cashier')
                        ->label('Tampilkan Kasir')
                        ->default(true),

                    Toggle::make('show_invoice_number')
                        ->label('Tampilkan Nomor Invoice')
                        ->default(true),

                    Toggle::make('show_payment_method')
                        ->label('Tampilkan Metode Pembayaran')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Footer Struk')
                ->schema([
                    Toggle::make('show_footer')
                        ->label('Tampilkan Footer')
                        ->default(true)
                        ->live(),

                    Textarea::make('footer_text')
                        ->label('Text Footer')
                        ->placeholder('Terima kasih telah berbelanja.')
                        ->rows(3)
                        ->maxLength(500)
                        ->visible(fn ($get): bool => (bool) $get('show_footer')),
                ]),

            Section::make('Pajak (PPN)')
                ->schema([
                    Toggle::make('show_tax')
                        ->label('Tampilkan PPN di Struk')
                        ->default(true),

                    TextInput::make('tax_rate')
                        ->label('Tarif PPN (%)')
                        ->numeric()
                        ->default(11)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),
                ])
                ->columns(2),
        ]);
    }
}

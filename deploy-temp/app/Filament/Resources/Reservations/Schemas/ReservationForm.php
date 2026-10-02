<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Models\Customer;
use App\Models\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Tamu')
                ->description('Data pemesan.')
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Nama Tamu')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('customer_phone')
                        ->label('No. HP')
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('customer_email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    Select::make('customer_id')
                        ->label('Customer/Member')
                        ->options(function () {
                            return Customer::query()
                                ->where('tenant_id', filament()->getTenant()?->id)
                                ->where('is_member', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Detail Reservasi')
                ->description('Waktu dan jumlah tamu.')
                ->schema([
                    DatePicker::make('reservation_date')
                        ->label('Tanggal')
                        ->required()
                        ->minDate(now())
                        ->default(now()),

                    TimePicker::make('reservation_time')
                        ->label('Waktu')
                        ->required()
                        ->seconds(false)
                        ->default('12:00'),

                    TextInput::make('guest_count')
                        ->label('Jumlah Tamu')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1),

                    Select::make('table_id')
                        ->label('Meja')
                        ->options(function () {
                            return Table::query()
                                ->where('tenant_id', filament()->getTenant()?->id)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Dikonfirmasi',
                            'seated' => 'Ditempatkan',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('pending')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Catatan')
                ->description('Catatan tambahan.')
                ->schema([
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Meja')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('Contoh: Meja 1, Meja VIP, Counter'),

                TextInput::make('table_number')
                    ->label('Nomor Meja')
                    ->maxLength(20)
                    ->placeholder('Nomor alternatif (opsional)'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia (Kosong)',
                        'active' => 'Sedang Digunakan',
                        'reserved' => 'Dipesan',
                    ])
                    ->default('available')
                    ->required(),

                TextInput::make('capacity')
                    ->label('Kapasitas (Orang)')
                    ->numeric()
                    ->default(4)
                    ->minValue(1)
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder('Opsional'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}

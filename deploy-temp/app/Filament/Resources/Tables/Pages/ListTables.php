<?php

namespace App\Filament\Resources\Tables\Pages;

use App\Filament\Pages\Tables\TablesOverview;
use App\Filament\Resources\Tables\TableResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;

class ListTables extends ListRecords
{
    protected static string $resource = TableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('overview')
                ->label('Overview')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(TablesOverview::getUrl()),
            CreateAction::make()
                ->label('Tambah Meja')
                ->slideOver(false)
                ->modalHeading('Tambah Meja Baru')
                ->modalWidth('md')
                ->form([
                    TextInput::make('name')
                        ->label('Nama Meja')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('table_number')
                        ->label('Nomor Meja')
                        ->maxLength(20),
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
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->createAnother(false),
        ];
    }
}

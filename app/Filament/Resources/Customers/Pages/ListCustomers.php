<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Customer')
                ->slideOver(false)
                ->modalHeading('Tambah Customer Baru')
                ->modalWidth('lg')
                ->form([
                    TextInput::make('name')
                        ->label('Nama Customer')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Nomor HP')
                        ->tel()
                        ->maxLength(30),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(2)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Customer Aktif')
                        ->default(true),
                ])
                ->createAnother(false),
        ];
    }
}

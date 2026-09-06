<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Supplier')
                ->slideOver(false)
                ->modalHeading('Tambah Supplier Baru')
                ->modalWidth('md')
                ->form([
                    TextInput::make('name')
                        ->label('Nama Supplier')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Nomor HP')
                        ->tel()
                        ->maxLength(50),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(2)
                        ->columnSpanFull(),
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

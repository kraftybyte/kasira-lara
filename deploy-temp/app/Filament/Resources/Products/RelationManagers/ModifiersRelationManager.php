<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModifiersRelationManager extends RelationManager
{
    protected static string $relationship = 'modifiers';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Modifier')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'addon',
                        'success' => 'option',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'addon' ? 'Extra/Add-on' : 'Pilihan'),

                TextColumn::make('price_adjustment')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Modifier')
                    ->modalHeading('Tambah Modifier')
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Modifier')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Extra Keju, Level Pedas'),

                        Select::make('type')
                            ->label('Tipe Modifier')
                            ->options([
                                'addon' => 'Extra/Add-on (Tambah Harga)',
                                'option' => 'Pilihan (Tidak Tambah Harga)',
                            ])
                            ->required()
                            ->default('addon'),

                        TextInput::make('price_adjustment')
                            ->label('Penyesuaian Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->helperText('Positif = tambah harga, Negatif = kurang harga'),

                        Textarea::make('description')
                            ->label('Deskripsi (Opsional)')
                            ->maxLength(500),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampilan')
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Modifier')
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Modifier')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Tipe Modifier')
                            ->options([
                                'addon' => 'Extra/Add-on (Tambah Harga)',
                                'option' => 'Pilihan (Tidak Tambah Harga)',
                            ])
                            ->required(),

                        TextInput::make('price_adjustment')
                            ->label('Penyesuaian Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Positif = tambah harga, Negatif = kurang harga'),

                        Textarea::make('description')
                            ->label('Deskripsi (Opsional)')
                            ->maxLength(500),

                        Toggle::make('is_active')
                            ->label('Aktif'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampilan')
                            ->numeric(),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Belum ada modifier')
            ->emptyStateDescription('Tambahkan modifier untuk produk ini seperti extra topping, level pedas, dll.');
    }
}

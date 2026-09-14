<?php

namespace App\Filament\Resources\TenantResource;

use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    protected static ?int $navigationSort = 50;

    // Disable tenant scoping for Tenant resource
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informasi Tenant')
                    ->description('Data utama tenant/bisnis.')
                    ->schema([

                        TextInput::make('name')
                            ->label('Nama Bisnis')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Akan digunakan untuk URL pesanan customer: /order/{slug}'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Kontak')
                    ->schema([

                        TextInput::make('phone')
                            ->label('Nomor HP')
                            ->tel()
                            ->maxLength(20),

                        Select::make('currency')
                            ->label('Mata Uang')
                            ->options([
                                'IDR' => 'Rupiah (IDR)',
                                'USD' => 'Dollar (USD)',
                            ])
                            ->default('IDR'),

                        Select::make('timezone')
                            ->label('Zona Waktu')
                            ->options([
                                'Asia/Jakarta' => 'WIB (UTC+7)',
                                'Asia/Makassar' => 'WITA (UTC+8)',
                                'Asia/Jayapura' => 'WIT (UTC+9)',
                            ])
                            ->default('Asia/Jakarta'),
                    ])
                    ->columns(3),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Tenant Aktif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug berhasil disalin')
                    ->color('primary'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('currency')
                    ->label('Mata Uang')
                    ->badge(),

                TextColumn::make('timezone')
                    ->label('Zona Waktu')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([

                SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\TenantResource;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tenant')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nama Bisnis')
                            ->prefixIcon('heroicon-o-building-office'),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('url-bisnis')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Akan digunakan untuk URL pesanan customer: /order/{slug}'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('email@bisnis.com')
                            ->prefixIcon('heroicon-o-envelope'),
                    ])->columns(3),

                Forms\Components\Section::make('Kontak')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('08xxxxxxxxxx')
                            ->prefixIcon('heroicon-o-phone'),

                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Asia/Jakarta' => 'WIB (Jakarta)',
                                'Asia/Makassar' => 'WITA (Makassar)',
                                'Asia/Jayapura' => 'WIT (Jayapura)',
                            ])
                            ->default('Asia/Jakarta')
                            ->searchable(),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'IDR' => 'Rupiah (IDR)',
                                'USD' => 'Dollar (USD)',
                            ])
                            ->default('IDR'),
                    ])->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Tenant yang tidak aktif tidak akan muncul di sistem'),
                    ]),

                Forms\Components\Hidden::make('status')
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug berhasil disalin')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('timezone')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),

                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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

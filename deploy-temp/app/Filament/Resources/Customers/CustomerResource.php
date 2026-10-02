<?php

namespace App\Filament\Resources\Customers;

use App\Models\Customer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?int $navigationSort = 10;

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informasi Customer')
                    ->description('Data utama customer.')
                    ->schema([

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
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Customer Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Membership')
                    ->description('Pengaturan status dan level member customer.')
                    ->schema([

                        Toggle::make('is_member')
                            ->label('Member')
                            ->helperText(
                                'Aktifkan jika customer merupakan member.'
                            )
                            ->default(false)
                            ->live(),

                        TextInput::make('member_code')
                            ->label('Member ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Otomatis dibuat'),

                        Select::make('member_level')
                            ->label('Level Member')
                            ->options([
                                'Bronze' => 'Bronze',
                                'Silver' => 'Silver',
                                'Gold' => 'Gold',
                                'Platinum' => 'Platinum',
                            ])
                            ->default('Bronze')
                            ->visible(
                                fn ($get): bool => (bool) $get('is_member')
                            ),

                        Select::make('tier')
                            ->label('Tier')
                            ->options([
                                'bronze' => 'Bronze (0% discount)',
                                'silver' => 'Silver (5% discount)',
                                'gold' => 'Gold (10% discount)',
                                'platinum' => 'Platinum (15% discount)',
                            ])
                            ->default('bronze')
                            ->visible(
                                fn ($get): bool => (bool) $get('is_member')
                            ),

                        TextInput::make('tier_discount')
                            ->label('Discount Tier (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->helperText('Otomatis dari tier, atau override manual')
                            ->visible(
                                fn ($get): bool => (bool) $get('is_member')
                            ),

                        DatePicker::make('joined_at')
                            ->label('Tanggal Bergabung')
                            ->default(now())
                            ->visible(
                                fn ($get): bool => (bool) $get('is_member')
                            ),
                    ])
                    ->columns(2),

                Section::make('Statistik Member')
                    ->description(
                        'Informasi transaksi dan loyalty customer.'
                    )
                    ->schema([

                        TextInput::make('points')
                            ->label('Point')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('total_spent')
                            ->label('Total Belanja')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('total_transactions')
                            ->label('Total Transaksi')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->visible(
                        fn ($get): bool => (bool) $get('is_member')
                    ),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('member_code')
                    ->label('Member ID')
                    ->searchable()
                    ->placeholder('-')
                    ->badge(),

                Tables\Columns\TextColumn::make('member_level')
                    ->label('Level')
                    ->badge()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'amber' => 'bronze',
                        'gray' => 'silver',
                        'yellow' => 'gold',
                        'sky' => 'platinum',
                    ])
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('points')
                    ->label('Point')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Belanja')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Transaksi')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_member')
                    ->label('Member')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])

            ->filters([

                Tables\Filters\TernaryFilter::make('is_member')
                    ->label('Member'),

                Tables\Filters\SelectFilter::make('member_level')
                    ->label('Level Member')
                    ->options([
                        'Bronze' => 'Bronze',
                        'Silver' => 'Silver',
                        'Gold' => 'Gold',
                        'Platinum' => 'Platinum',
                    ]),

                Tables\Filters\SelectFilter::make('tier')
                    ->label('Tier')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])

            /*
            |--------------------------------------------------------------------------
            | RECORD ACTIONS
            |--------------------------------------------------------------------------
            */

            ->recordActions([
                EditAction::make(),
            ])

            /*
            |--------------------------------------------------------------------------
            | TOOLBAR ACTIONS
            |--------------------------------------------------------------------------
            */

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort(
                'created_at',
                'desc'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),

            'create' => Pages\CreateCustomer::route('/create'),

            'view' => Pages\ViewCustomer::route('/{record}'),

            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}

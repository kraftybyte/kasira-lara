<?php

namespace App\Filament\Resources\UserResource;

use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 100;

    protected static bool $shouldAutoDiscoverRelatedResources = false;

    protected static bool $shouldAutoDiscoverRelations = false;

    // Disable tenant scoping - users use many-to-many relationship
    protected static bool $isScopedToTenant = false;

    protected static ?string $tenantOwnershipRelationship = null;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(User::class, ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->password()
                    ->required(fn ($context) => $context === 'create')
                    ->confirmed()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn ($context) => $context === 'create'),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->options(function () {
                        // Filter out super_admin role for non-super_admin users
                        $user = auth()->user();
                        if ($user && $user->hasRole('super_admin')) {
                            return Role::pluck('name', 'id')->toArray();
                        }

                        return Role::where('name', '!=', 'super_admin')->pluck('name', 'id')->toArray();
                    }),

                Select::make('tenants')
                    ->multiple()
                    ->label('Assign to Tenants')
                    ->relationship('tenants', 'name')
                    ->options(function () {
                        return Tenant::where('is_active', true)->pluck('name', 'id')->toArray();
                    })
                    ->helperText('Assign this user to one or more tenants'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = User::query()->with('roles');

                $user = auth()->user();

                // Super admin can see all users
                if ($user && $user->hasRole('super_admin')) {
                    return $query;
                }

                // Other users only see users assigned to their tenants
                return $query
                    ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                    ->whereHas('tenants', function ($q) use ($user) {
                        if ($user) {
                            $q->whereIn('tenants.id', $user->tenants()->pluck('tenants.id'));
                        }
                    });
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TagsColumn::make('roles.name')
                    ->label('Roles'),

                TextColumn::make('tenants.name')
                    ->label('Tenants')
                    ->badge(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}

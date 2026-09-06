<?php

namespace App\Filament\Resources\TenantReceiptSettings;

use App\Filament\Resources\TenantReceiptSettings\Pages\EditTenantReceiptSetting;
use App\Filament\Resources\TenantReceiptSettings\Pages\ListTenantReceiptSettings;
use App\Filament\Resources\TenantReceiptSettings\Schemas\TenantReceiptSettingForm;
use App\Filament\Resources\TenantReceiptSettings\Tables\TenantReceiptSettingsTable;
use App\Models\TenantReceiptSetting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class TenantReceiptSettingResource extends Resource
{
    protected static ?string $model = TenantReceiptSetting::class;

    protected static ?string $navigationLabel = 'Pengaturan Struk';

    protected static ?string $modelLabel = 'Pengaturan Struk';

    protected static ?string $pluralModelLabel = 'Pengaturan Struk';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    public static function form(Schema $schema): Schema
    {
        return TenantReceiptSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantReceiptSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenantReceiptSettings::route('/'),
            'edit' => EditTenantReceiptSetting::route('/{record}/edit'),
        ];
    }
}

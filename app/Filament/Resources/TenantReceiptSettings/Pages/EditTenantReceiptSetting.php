<?php

namespace App\Filament\Resources\TenantReceiptSettings\Pages;

use App\Filament\Resources\TenantReceiptSettings\TenantReceiptSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditTenantReceiptSetting extends EditRecord
{
    protected static string $resource = TenantReceiptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return TenantReceiptSettingResource::getUrl();
    }
}

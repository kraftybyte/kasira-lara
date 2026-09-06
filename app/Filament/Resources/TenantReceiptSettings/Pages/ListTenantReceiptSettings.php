<?php

namespace App\Filament\Resources\TenantReceiptSettings\Pages;

use App\Filament\Resources\TenantReceiptSettings\TenantReceiptSettingResource;
use App\Models\TenantReceiptSetting;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListTenantReceiptSettings extends ListRecords
{
    protected static string $resource = TenantReceiptSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return;
        }

        $setting = TenantReceiptSetting::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
            ],
            [
                'store_name' => $tenant->name,
                'show_logo' => true,
                'show_customer' => true,
                'show_cashier' => true,
                'show_payment_method' => true,
                'paper_size' => '80mm',
            ]
        );

        $this->redirect(
            TenantReceiptSettingResource::getUrl(
                'edit',
                [
                    'record' => $setting,
                ]
            )
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

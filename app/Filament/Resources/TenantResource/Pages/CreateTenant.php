<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Pembuat langsung jadi anggota aktif, supaya tenant baru bisa dibuka dari pemilih tenant.
     */
    protected function afterCreate(): void
    {
        auth()->user()?->tenants()->syncWithoutDetaching([$this->record->getKey() => ['status' => 'active']]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Kitchen extends Page
{
    protected string $view = 'filament.pages.kitchen';

    protected static ?string $title = 'Kitchen Display';

    protected static ?string $slug = 'kitchen';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    public function getTenant()
    {
        return Filament::getTenant();
    }

    public function getPendingOrders(): Collection
    {
        $tenant = $this->getTenant();
        if (! $tenant) {
            return collect();
        }

        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['completed', 'ready', 'cancelled'])
            ->with(['items', 'table', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getReadyOrders(): Collection
    {
        $tenant = $this->getTenant();
        if (! $tenant) {
            return collect();
        }

        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'ready')
            ->with(['items', 'table', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function markAsPreparing(int $saleId): void
    {
        $tenant = $this->getTenant();
        Sale::where('id', $saleId)
            ->where('tenant_id', $tenant?->id)
            ->update(['status' => 'preparing']);
    }

    public function markAsReady(int $saleId): void
    {
        $tenant = $this->getTenant();
        Sale::where('id', $saleId)
            ->where('tenant_id', $tenant?->id)
            ->update(['status' => 'ready']);
    }

    public function markAsCompleted(int $saleId): void
    {
        $tenant = $this->getTenant();
        Sale::where('id', $saleId)
            ->where('tenant_id', $tenant?->id)
            ->update(['status' => 'completed']);
    }
}

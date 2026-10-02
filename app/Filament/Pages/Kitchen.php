<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Throwable;

class Kitchen extends Page
{
    protected string $view = 'filament.pages.kitchen';

    protected static ?string $title = 'Kitchen Display';

    protected static ?string $slug = 'kitchen';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 20;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedFire;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // All roles can access Kitchen: super_admin, owner, kepala_toko, cashier, kitchen
        // Kitchen staff need to see orders
        return $user->hasAnyRole(['super_admin', 'owner', 'kepala_toko', 'cashier', 'kitchen']);
    }

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

        // Orders that need cooking: not yet served, not kitchen completed
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('closed_at')
            ->whereNull('served_at')
            ->whereNull('kitchen_completed_at')
            ->with(['items.modifiers', 'items.product', 'table', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getReadyOrders(): Collection
    {
        $tenant = $this->getTenant();
        if (! $tenant) {
            return collect();
        }

        // Orders that are marked as served - show regardless of payment status
        // Not kitchen completed yet
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('closed_at')
            ->whereNotNull('served_at')
            ->whereNull('kitchen_completed_at')
            ->with(['items.modifiers', 'items.product', 'table', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function markAsServed(int $saleId): void
    {
        try {
            $tenant = $this->getTenant();
            Sale::where('id', $saleId)
                ->where('tenant_id', $tenant?->id)
                ->update(['served_at' => now()]);

            Notification::make()
                ->title('Berhasil')
                ->body('Pesanan ditandai siap disajikan.')
                ->success()
                ->send();

            // Refresh the page
            $this->redirect(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]), navigate: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function markAsUnserved(int $saleId): void
    {
        try {
            $tenant = $this->getTenant();
            Sale::where('id', $saleId)
                ->where('tenant_id', $tenant?->id)
                ->update(['served_at' => null]);

            Notification::make()
                ->title('Berhasil')
                ->body('Pesanan dikembalikan ke daftar masak.')
                ->success()
                ->send();

            // Refresh the page
            $this->redirect(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]), navigate: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function markAsCompleted(int $saleId): void
    {
        try {
            $tenant = $this->getTenant();
            // Set kitchen_completed_at - does NOT affect payment status
            // Payment status (open/pending/completed) is managed separately by kasir
            $updated = Sale::where('id', $saleId)
                ->where('tenant_id', $tenant?->id)
                ->update([
                    'kitchen_completed_at' => now(),
                ]);

            if ($updated) {
                Notification::make()
                    ->title('Berhasil')
                    ->body('Pesanan ditutup dari dapur.')
                    ->success()
                    ->send();

                // Refresh the page
                $this->redirect(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]), navigate: true);
            } else {
                Notification::make()
                    ->title('Gagal')
                    ->body('Pesanan tidak ditemukan.')
                    ->warning()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}

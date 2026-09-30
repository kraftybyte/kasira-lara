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

        // Orders that need cooking: not yet served, not closed
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('closed_at')
            ->whereNull('served_at')
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

        // Orders that are marked as served - show regardless of status
        // Orders with served_at=null are in "Sedang Dimasak"
        // When status='completed', order is hidden by setting closed_at (done by closeTable)
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('closed_at')
            ->whereNotNull('served_at')
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
            // Set both status and closed_at so order disappears from Kitchen
            $updated = Sale::where('id', $saleId)
                ->where('tenant_id', $tenant?->id)
                ->update([
                    'status' => 'completed',
                    'closed_at' => now(),  // This makes order disappear from Kitchen queries
                ]);

            if ($updated) {
                Notification::make()
                    ->title('Berhasil')
                    ->body('Pesanan ditutup.')
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

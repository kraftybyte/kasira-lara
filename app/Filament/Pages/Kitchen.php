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

        // Orders that are not yet served
        // POS orders: show all (already paid when created)
        // QR scan orders: only show if payment is confirmed (paid or completed)
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('served_at')
            ->where(function ($query) {
                $query->where('source', 'pos')
                    ->orWhere(function ($q) {
                        $q->where('source', 'customer')
                            ->where(function ($inner) {
                                $inner->where('payment_status', 'paid')
                                    ->orWhere('status', 'completed');
                            });
                    });
            })
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

        // Orders that are ready to serve (not yet completed)
        // Only show POS orders or paid customer orders
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('served_at')
            ->where('status', '!=', 'completed')
            ->where(function ($query) {
                $query->where('source', 'pos')
                    ->orWhere(function ($q) {
                        $q->where('source', 'customer')
                            ->where(function ($inner) {
                                $inner->where('payment_status', 'paid')
                                    ->orWhere('status', 'completed');
                            });
                    });
            })
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

            redirect()->to(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]));
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

            redirect()->to(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]));
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
            $updated = Sale::where('id', $saleId)
                ->where('tenant_id', $tenant?->id)
                ->update(['status' => 'completed']);

            if ($updated) {
                Notification::make()
                    ->title('Berhasil')
                    ->body('Pesanan ditutup.')
                    ->success()
                    ->send();

                redirect()->to(route('filament.admin.pages.kitchen', ['tenant' => $tenant?->getRouteKey()]));
            } else {
                Notification::make()
                    ->title('Gagal')
                    ->body('Pesanan tidak ditemukan.')
                    ->warning()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}

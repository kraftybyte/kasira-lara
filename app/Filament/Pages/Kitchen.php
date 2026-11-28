<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Throwable;

class Kitchen extends Page
{
    protected string $view = 'filament.pages.kitchen';

    protected static ?string $title = 'Dapur';

    protected static ?string $slug = 'kitchen';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 4;

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

    /** @var array<int, int> */
    #[Locked]
    public array $seenOrderIds = [];

    /**
     * ?display=1: tanpa sidebar/header admin, huruf lebih besar, untuk TV atau tablet dapur.
     */
    #[Url]
    public bool $display = false;

    /**
     * Judul dan breadcrumb bawaan Filament dimatikan; view punya header sendiri.
     */
    public function getHeader(): ?View
    {
        return null;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getLayout(): string
    {
        return $this->display ? 'filament-panels::components.layout.base' : parent::getLayout();
    }

    public function mount(): void
    {
        $this->seenOrderIds = $this->pendingOrders->pluck('id')->all();
    }

    /**
     * Dipanggil tiap 5 detik dari view; beri tahu dapur kalau ada pesanan masuk.
     */
    public function poll(): void
    {
        $currentIds = $this->pendingOrders->pluck('id')->all();

        if (array_diff($currentIds, $this->seenOrderIds) !== []) {
            $this->dispatch('kitchen-new-order');
        }

        $this->seenOrderIds = $currentIds;
    }

    /**
     * Dihitung sekali per render; view memanggilnya berkali-kali.
     */
    #[Computed]
    public function pendingOrders(): Collection
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
            ->with(['items.modifiers', 'table'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    #[Computed]
    public function readyOrders(): Collection
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
            ->with(['items.modifiers', 'table'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Render ulang di tempat, bukan memuat ulang halaman: izin suara dan daftar pesanan yang sudah dilihat tetap tersimpan.
     */
    protected function refreshBoard(): void
    {
        unset($this->pendingOrders, $this->readyOrders);
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
            $this->refreshBoard();
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
            $this->refreshBoard();
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
                $this->refreshBoard();
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

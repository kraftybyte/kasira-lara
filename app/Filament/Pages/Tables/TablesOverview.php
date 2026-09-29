<?php

namespace App\Filament\Pages\Tables;

use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Table;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class TablesOverview extends Page
{
    protected static ?string $title = 'Meja';

    protected static ?string $slug = 'tables-overview';

    protected string $view = 'filament.pages.tables-overview';

    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';

    public ?int $focusedTableId = null;

    public function mount(): void
    {
        $tableId = request()->query('table');
        if ($tableId) {
            $this->focusedTableId = (int) $tableId;
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only: super_admin, owner, kepala_toko can access Tables
        return $user->hasAnyRole(['super_admin', 'owner', 'kepala_toko']);
    }

    #[Computed]
    public function tables(): Collection
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return collect();
        }

        return Table::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableTables(): Collection
    {
        // Available = completely free, NO orders at all (pending/open/completed)
        // AND status is available AND not reserved
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        $tableIdsWithAnyOrders = Sale::where('tenant_id', $tenant->id)
            ->whereNotNull('table_id')
            ->whereNotIn('status', ['cancelled'])
            ->pluck('table_id')
            ->unique()
            ->toArray();

        return $this->tables->filter(function ($table) use ($tableIdsWithAnyOrders) {
            // Available if: NO orders at all AND status is available AND not reserved
            return ! in_array($table->id, $tableIdsWithAnyOrders)
                && $table->status === 'available';
        });
    }

    #[Computed]
    public function activeTables(): Collection
    {
        // Active = has orders (pending/open/completed) AND not reserved
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        $tableIdsWithOrders = Sale::where('tenant_id', $tenant->id)
            ->whereNotNull('table_id')
            ->whereNotIn('status', ['cancelled'])
            ->pluck('table_id')
            ->unique()
            ->toArray();

        return $this->tables->filter(function ($table) use ($tableIdsWithOrders) {
            // Active only if: has orders AND not reserved
            return in_array($table->id, $tableIdsWithOrders)
                && $table->status !== 'reserved';
        });
    }

    #[Computed]
    public function reservedTables(): Collection
    {
        return $this->tables->where('status', 'reserved');
    }

    #[Computed]
    public function todayReservations(): Collection
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return collect();
        }

        return Reservation::query()
            ->where('tenant_id', $tenant->id)
            ->where('reservation_date', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('table')
            ->orderBy('reservation_time')
            ->get();
    }

    #[Computed]
    public function upcomingReservations(): Collection
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return collect();
        }

        return Reservation::query()
            ->where('tenant_id', $tenant->id)
            ->where('reservation_date', '>', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('table')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function tableOrders(): Collection
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return collect();
        }

        // Get ALL orders for this tenant with valid table_id
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('table_id')
            ->with(['table', 'items'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('table_id');
    }

    #[Computed]
    public function ordersWithoutTable(): Collection
    {
        // Get orders without table_id (QR Meja customer orders)
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('table_id')
            ->whereIn('status', ['open', 'pending', 'completed'])
            ->with(['items'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function completedTableOrders(): Collection
    {
        // Empty - we now include all orders in tableOrders
        return collect();
    }

    public function getTableOrders(int $tableId): Collection
    {
        return $this->tableOrders[$tableId] ?? collect();
    }

    public function getTablePendingTotal(int $tableId): float
    {
        $orders = $this->tableOrders[$tableId] ?? collect();

        // Only unpaid cash orders need collection
        return $orders->where('status', 'pending')
            ->where('payment_method', 'cash')
            ->sum('grand_total');
    }

    public function getTableCompletedTotal(int $tableId): float
    {
        $orders = $this->tableOrders[$tableId] ?? collect();

        // Already paid orders (QRIS/Transfer completed)
        return $orders->where('status', 'completed')
            ->sum('grand_total');
    }

    /*
    |--------------------------------------------------------------------------
    | MARK AS SERVED
    |--------------------------------------------------------------------------
    */

    public function markAsServed(int $saleId): void
    {
        $sale = Sale::find($saleId);

        if ($sale) {
            $sale->markAsServed();
            $this->reset('tableOrders');
        }
    }

    public function markAsUnserved(int $saleId): void
    {
        $sale = Sale::find($saleId);

        if ($sale) {
            $sale->markAsUnserved();
            $this->reset('tableOrders');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESERVATION ACTIONS
    |--------------------------------------------------------------------------
    */

    public function seatReservation(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);

        if (! $reservation) {
            return;
        }

        // Update reservation status
        $reservation->update(['status' => 'seated']);

        // Update table status to active
        if ($reservation->table_id) {
            $table = Table::find($reservation->table_id);
            if ($table && $table->status === 'available') {
                $table->update(['status' => 'active']);
            }
        }

        $this->reset('todayReservations');

        Notification::make()
            ->title('Tamu ditempatkan')
            ->body("{$reservation->customer_name} sudah di tempatkan di meja {$reservation->table?->name}.")
            ->success()
            ->send();
    }

    public function confirmReservation(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);

        if (! $reservation) {
            return;
        }

        $reservation->update(['status' => 'confirmed']);

        $this->reset('todayReservations');

        Notification::make()
            ->title('Reservasi dikonfirmasi')
            ->body("Reservasi untuk {$reservation->customer_name} sudah dikonfirmasi.")
            ->success()
            ->send();
    }

    public function cancelReservation(int $reservationId): void
    {
        $reservation = Reservation::find($reservationId);

        if (! $reservation) {
            return;
        }

        $reservation->update(['status' => 'cancelled']);

        $this->reset('todayReservations');

        Notification::make()
            ->title('Reservasi dibatalkan')
            ->body("Reservasi untuk {$reservation->customer_name} sudah dibatalkan.")
            ->warning()
            ->send();
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE TABLE CONFIRMATION
    |--------------------------------------------------------------------------
    */

    public bool $showCloseTableConfirm = false;

    public ?int $pendingCloseTableId = null;

    public ?string $pendingCloseTableName = null;

    public function requestCloseTable(int $tableId, string $tableName): void
    {
        $this->showCloseTableConfirm = true;
        $this->pendingCloseTableId = $tableId;
        $this->pendingCloseTableName = $tableName;

        // Dispatch event to show toast
        $this->dispatch('show-close-table-toast', [
            'tableId' => $tableId,
            'tableName' => $tableName,
        ]);
    }

    public function confirmCloseTable(): void
    {
        if (! $this->pendingCloseTableId) {
            $this->showCloseTableConfirm = false;

            return;
        }

        $this->closeTable($this->pendingCloseTableId);

        // Reset state
        $this->showCloseTableConfirm = false;
        $this->pendingCloseTableId = null;
        $this->pendingCloseTableName = null;
    }

    public function cancelCloseTable(): void
    {
        $this->showCloseTableConfirm = false;
        $this->pendingCloseTableId = null;
        $this->pendingCloseTableName = null;
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE TABLE
    |--------------------------------------------------------------------------
    */

    public function closeTable(int $tableId): void
    {
        $table = Table::find($tableId);

        if (! $table) {
            return;
        }

        // Reset the table to available
        $table->update(['status' => 'available']);

        Notification::make()
            ->title('Bill ditutup')
            ->body("Meja {$table->name} sudah bersih, siap untuk pelanggan baru.")
            ->success()
            ->send();
    }

    public function hasUnpaidOrders(int $tableId): bool
    {
        return Sale::where('table_id', $tableId)
            ->where('status', 'pending')
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | GET TABLE ORDERS FOR MODAL
    |--------------------------------------------------------------------------
    */

    public function getTableOrdersForModal(int $tableId): array
    {
        $orders = $this->tableOrders[$tableId] ?? collect();

        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'grand_total' => (float) $order->grand_total,
                'served_at' => $order->served_at?->toIso8601String(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}

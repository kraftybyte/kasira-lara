<?php

namespace App\Filament\Pages\Tables;

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

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

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
        return $this->tables->where('status', 'available');
    }

    #[Computed]
    public function activeTables(): Collection
    {
        return $this->tables->where('status', 'active');
    }

    #[Computed]
    public function reservedTables(): Collection
    {
        return $this->tables->where('status', 'reserved');
    }

    #[Computed]
    public function tableOrders(): Collection
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return collect();
        }

        // Get all orders (open = active, pending = unpaid cash, completed = paid)
        return Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['open', 'pending', 'completed'])
            ->whereHas('table', function ($query) {
                $query->where('status', '!=', 'available');
            })
            ->with(['table', 'items'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('table_id');
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
    | CLOSE TABLE
    |--------------------------------------------------------------------------
    */

    public function closeTable(int $tableId): void
    {
        $table = Table::find($tableId);

        if (! $table) {
            return;
        }

        // Mark all orders as completed (finished)
        Sale::where('table_id', $tableId)
            ->update([
                'status' => 'completed',
            ]);

        // Close the table - set status to available
        $table->update(['status' => 'available']);

        Notification::make()
            ->title('Meja ditutup')
            ->body("Meja {$table->name} siap untuk pelanggan baru.")
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

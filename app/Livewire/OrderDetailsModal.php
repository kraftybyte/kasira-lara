<?php

namespace App\Livewire;

use App\Filament\Pages\Tables\TablesOverview;
use App\Models\Sale;
use App\Models\Table;
use Livewire\Component;

class OrderDetailsModal extends Component
{
    public bool $isOpen = false;

    public ?int $selectedTableId = null;

    public ?string $selectedTableName = null;

    public ?int $selectedTenantId = null;

    public ?string $selectedTenantSlug = null;

    public bool $showBulkPayConfirm = false;

    public $orders = [];

    protected $listeners = [
        'showOrderDetails' => 'openModal',
        'showAllOrders' => 'openAllOrdersModal',
    ];

    public function openAllOrdersModal()
    {
        $this->selectedTableId = null;
        $this->selectedTableName = 'Semua Pesanan';

        // Get tenant from first table
        $table = Table::first();
        $tenant = $table?->tenant;
        $this->selectedTenantId = $tenant?->id;
        $this->selectedTenantSlug = $tenant?->slug;

        $this->loadAllOrders();
        $this->isOpen = true;
    }

    public function openModal($tableId, $tableName)
    {
        $this->selectedTableId = $tableId;
        $this->selectedTableName = $tableName;

        // Get tenant info from table
        $table = Table::find($tableId);
        $tenant = $table?->tenant;
        $this->selectedTenantId = $tenant?->id;
        $this->selectedTenantSlug = $tenant?->slug;

        $this->loadOrders();
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->selectedTableId = null;
        $this->selectedTableName = null;
        $this->selectedTenantId = null;
        $this->selectedTenantSlug = null;
        $this->orders = [];
        $this->showBulkPayConfirm = false;
    }

    public function loadOrders()
    {
        if (! $this->selectedTableId) {
            // Load ALL orders grouped by table (for "Semua Pesanan")
            $this->loadAllOrders();

            return;
        }

        // Load ALL orders for specific table - no status filter (show until closed)
        $sales = Sale::query()
            ->where('table_id', $this->selectedTableId)
            ->with(['items.product', 'table'])
            ->orderByDesc('created_at')
            ->get();

        // Group by table_id
        $grouped = $sales->groupBy(function ($sale) {
            return $sale->table_id ?? 'no_table';
        });

        $this->orders = $grouped->map(function ($orders, $tableId) {
            $firstOrder = $orders->first();
            $tableName = $firstOrder->table?->name ?? 'Tanpa Meja';

            return [
                'table_id' => $tableId,
                'table_name' => $tableName,
                'count' => $orders->count(),
                'total' => $orders->sum('grand_total'),
                'orders' => $orders->map(function ($sale) {
                    return $this->mapSaleToArray($sale);
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }

    public function loadAllOrders()
    {
        // Load ALL orders grouped by table - no status filter (show until table is closed)
        $sales = Sale::query()
            ->where('tenant_id', $this->selectedTenantId)
            ->with(['items.product', 'table'])
            ->orderByDesc('created_at')
            ->get();

        // Group by table_id
        $grouped = $sales->groupBy(function ($sale) {
            return $sale->table_id ?? 'no_table';
        });

        $this->orders = $grouped->map(function ($orders, $tableId) {
            $firstOrder = $orders->first();
            $tableName = $firstOrder->table?->name ?? 'Tanpa Meja';

            return [
                'table_id' => $tableId,
                'table_name' => $tableName,
                'count' => $orders->count(),
                'total' => $orders->sum('grand_total'),
                'orders' => $orders->map(function ($sale) {
                    return $this->mapSaleToArray($sale);
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }

    protected function mapSaleToArray($sale): array
    {
        $hasDuration = $sale->items->contains(function ($item) {
            return $item->product && $item->product->rate_type === 'duration';
        });

        // Determine display status
        $displayStatus = match ($sale->status) {
            'open', 'pending' => $sale->served_at ? 'served' : 'pending',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'completed' => $sale->served_at ? 'served' : 'ready',
            'cancelled' => 'cancelled',
            default => 'pending',
        };

        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'status' => $sale->status,
            'display_status' => $displayStatus,
            'payment_method' => $sale->payment_method,
            'grand_total' => (float) $sale->grand_total,
            'notes' => $sale->notes,
            'served_at' => $sale->served_at?->toIso8601String(),
            'created_at' => $sale->created_at?->toIso8601String(),
            'has_duration' => $hasDuration,
            'table_name' => $sale->table?->name ?? 'Tanpa Meja',
            'items' => $sale->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'notes' => $item->notes,
                    'rate_type' => $item->product?->rate_type ?? 'fixed',
                    'is_duration' => $item->product?->rate_type === 'duration',
                ];
            })->toArray(),
        ];
    }

    public function markAsServed($saleId)
    {
        $sale = Sale::find($saleId);

        if ($sale) {
            $sale->markAsServed();
            $this->loadOrders();
            $this->dispatch('refreshTableOrders')->to(TablesOverview::class);
        }
    }

    public function markAsUnserved($saleId)
    {
        $sale = Sale::find($saleId);

        if ($sale) {
            $sale->markAsUnserved();
            $this->loadOrders();
            $this->dispatch('refreshTableOrders')->to(TablesOverview::class);
        }
    }

    /**
     * Bulk pay all pending counter orders for this table
     * Redirects to POS with counter orders loaded
     */
    public function bulkPayCounter()
    {
        if (! $this->selectedTableId) {
            return;
        }

        // Get tenant slug BEFORE closing modal (since closeModal resets it)
        $table = Table::find($this->selectedTableId);
        $tenantSlug = $table?->tenant?->slug;

        if (! $tenantSlug) {
            return;
        }

        // Get the counter orders info
        $pendingOrders = Sale::where('table_id', $this->selectedTableId)
            ->where('status', 'pending')
            ->where('payment_method', 'counter')
            ->pluck('id')
            ->toArray();

        if (empty($pendingOrders)) {
            return;
        }

        // Encode sale IDs as comma-separated string for URL
        $saleIdsString = implode(',', $pendingOrders);

        // Close modal first
        $this->closeModal();

        // Redirect to POS with tenant slug in URL
        return redirect()->to("/admin/{$tenantSlug}/pos?table={$this->selectedTableId}&bulk=counter&sale_ids={$saleIdsString}");
    }

    public function render()
    {
        return view('livewire.order-details-modal');
    }
}

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
    ];

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
            $this->orders = [];

            return;
        }

        $table = Table::find($this->selectedTableId);

        if (! $table) {
            $this->orders = [];

            return;
        }

        $this->orders = Sale::query()
            ->where('table_id', $this->selectedTableId)
            ->whereIn('status', ['open', 'pending', 'preparing', 'ready', 'completed'])
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($sale) {
                $hasDuration = $sale->items->contains(function ($item) {
                    return $item->product && $item->product->rate_type === 'duration';
                });

                // Determine display status
                $displayStatus = match ($sale->status) {
                    'open', 'pending' => $sale->served_at ? 'served' : 'pending',
                    'preparing' => 'preparing',
                    'ready' => 'ready',
                    'completed' => $sale->served_at ? 'served' : 'ready',
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
            })
            ->toArray();
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
        if (! $this->selectedTableId || ! $this->selectedTenantSlug) {
            return;
        }

        // Store the counter orders info in session for POS to load
        $pendingOrders = Sale::where('table_id', $this->selectedTableId)
            ->where('status', 'pending')
            ->where('payment_method', 'counter')
            ->pluck('id')
            ->toArray();

        if (empty($pendingOrders)) {
            return;
        }

        // Store sale IDs in session for POS to load
        session()->put('bulk_counter_sales', $pendingOrders);
        session()->put('bulk_counter_table_id', $this->selectedTableId);

        // Close modal and redirect to POS
        $this->closeModal();

        // Redirect to POS
        return redirect()->to("/admin/{$this->selectedTenantSlug}/pos?table={$this->selectedTableId}&bulk=counter");
    }

    public function render()
    {
        return view('livewire.order-details-modal');
    }
}

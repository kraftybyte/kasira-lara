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

    public $orders = [];

    protected $listeners = [
        'showOrderDetails' => 'openModal',
    ];

    public function openModal($tableId, $tableName)
    {
        $this->selectedTableId = $tableId;
        $this->selectedTableName = $tableName;

        // Get tenant_id from table
        $table = Table::find($tableId);
        $this->selectedTenantId = $table?->tenant_id;

        $this->loadOrders();
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->selectedTableId = null;
        $this->selectedTableName = null;
        $this->selectedTenantId = null;
        $this->orders = [];
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
            ->whereIn('status', ['open', 'pending', 'completed'])
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($sale) {
                $hasDuration = $sale->items->contains(function ($item) {
                    return $item->product && $item->product->rate_type === 'duration';
                });

                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'status' => $sale->status,
                    'payment_method' => $sale->payment_method,
                    'grand_total' => (float) $sale->grand_total,
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

    public function render()
    {
        return view('livewire.order-details-modal');
    }
}

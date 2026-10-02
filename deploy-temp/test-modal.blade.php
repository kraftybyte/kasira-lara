<?php

use App\Livewire\OrderDetailsModal;
use Illuminate\Support\Facades\Blade;

$tableId = request('table');
$tableName = 'Meja ' . $tableId;

$modal = app(OrderDetailsModal::class);
$modal->selectedTableId = (int) $tableId;
$modal->selectedTableName = $tableName;

$table = \App\Models\Table::find($tableId);
$modal->selectedTenantId = $table?->tenant_id;
$modal->selectedTenantSlug = $table?->tenant?->slug ?? 'kasira-demo';

$modal->loadOrders();
$modal->isOpen = true;

// Render component
$blade = $modal->render()->with([
    'isOpen' => $modal->isOpen,
    'selectedTableId' => $modal->selectedTableId,
    'selectedTableName' => $modal->selectedTableName,
    'selectedTenantId' => $modal->selectedTenantId,
    'selectedTenantSlug' => $modal->selectedTenantSlug,
    'orders' => $modal->orders,
    'showBulkPayConfirm' => $modal->showBulkPayConfirm,
])->withCaching()->render();

echo "<!-- Debug: selectedTableId={$modal->selectedTableId}, orders_count=" . count($modal->orders) . " -->\n";
echo $blade;

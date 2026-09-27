<?php

namespace App\Livewire;

use App\Models\Table;
use App\Models\Tenant;
use Livewire\Component;

class TableQrModal extends Component
{
    public bool $isOpen = false;

    public ?int $selectedTableId = null;

    public ?string $selectedTableName = null;

    protected $listeners = [
        'showQrCode' => 'openModal',
    ];

    public function openModal($tableId, $tableName)
    {
        $this->selectedTableId = $tableId;
        $this->selectedTableName = $tableName;
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->selectedTableId = null;
        $this->selectedTableName = null;
    }

    public function qrUrl(): ?string
    {
        if (! $this->selectedTableId) {
            return null;
        }

        $table = Table::find($this->selectedTableId);

        if (! $table) {
            return null;
        }

        $tenant = Tenant::find($table->tenant_id);

        if (! $tenant) {
            return null;
        }

        return url('/order/'.$tenant->getRouteKey().'/'.$table->id);
    }

    public function render()
    {
        return view('livewire.table-qr-modal');
    }
}

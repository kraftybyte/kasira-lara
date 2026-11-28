<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\KitchenBoard;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Layar pantau dapur tanpa login: dibuka lewat tautan bertoken dari halaman Dapur, hanya tampilan.
 */
#[Layout('layouts.kitchen-display')]
class KitchenDisplay extends Component
{
    #[Locked]
    public int $tenantId;

    /** @var array<int, int> */
    #[Locked]
    public array $seenOrderIds = [];

    public function mount(string $tenant, string $token): void
    {
        $tenantModel = Tenant::findBySlug($tenant);
        $expected = $tenantModel ? TenantSetting::where('tenant_id', $tenantModel->id)->value('kitchen_display_token') : null;

        abort_unless($tenantModel && filled($expected) && hash_equals((string) $expected, $token), 404);

        $this->tenantId = (int) $tenantModel->id;
        $this->seenOrderIds = KitchenBoard::pending($this->tenantId)->pluck('id')->all();
    }

    public function poll(): void
    {
        $currentIds = KitchenBoard::pending($this->tenantId)->pluck('id')->all();

        if (array_diff($currentIds, $this->seenOrderIds) !== []) {
            $this->dispatch('kitchen-new-order');
        }

        $this->seenOrderIds = $currentIds;
    }

    public function render(): View
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        return view('livewire.kitchen-display', [
            'tenant' => $tenant,
            'pendingOrders' => KitchenBoard::pending($this->tenantId),
            'readyOrders' => KitchenBoard::ready($this->tenantId),
            'sop' => KitchenBoard::sopMinutes($this->tenantId),
        ])->title('Layar Dapur '.$tenant->name);
    }
}

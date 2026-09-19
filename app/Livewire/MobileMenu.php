<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Livewire\Component;

class MobileMenu extends Component
{
    public function render()
    {
        $navigation = [];
        $tenant = null;
        $tenantId = '';

        try {
            $navigation = Filament::getNavigation();
            $tenant = Filament::getTenant();
            $tenantId = $tenant?->id ?? '';
        } catch (\Throwable $e) {
            // Navigation might not be available in all contexts
        }

        return view('livewire.mobile-menu', [
            'navigation' => $navigation,
            'tenant' => $tenant,
            'tenantId' => $tenantId,
        ]);
    }
}

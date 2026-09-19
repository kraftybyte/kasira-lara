<?php

namespace App\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

class MobileHeaderPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mobile-header';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            PanelsRenderHook::BODY_START,
            fn () => view('filament.components.mobile-header-livewire')
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

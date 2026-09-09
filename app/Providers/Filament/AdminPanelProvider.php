<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Settings\TenantSettings;
use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use ReflectionClass;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Super admin and owner bypasses all permission checks
        Gate::before(function ($user, $ability) {
            if ($user && ($user->hasRole('super_admin') || $user->hasRole('owner'))) {
                return true;
            }
        });

        // Set navigation icon for TenantSettings via static method
        TenantSettings::navigationIcon('heroicon-o-cog-6-tooth');

        // Set navigation sort using reflection
        $reflection = new ReflectionClass(TenantSettings::class);
        $prop = $reflection->getProperty('navigationSort');
        $prop->setValue(null, 10);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->sidebarWidth('280px')
            ->sidebarCollapsibleOnDesktop(false)
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')

            ->login()

            // TENANCY - Tenant menu moved to header via custom views
            ->tenant(Tenant::class)

            ->colors([
                'primary' => Color::hex('#EF4444'),
            ])

            ->topNavigation(false)

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\Filament\Resources'
            )

            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\Filament\Pages'
            )

            ->pages([
                TenantSettings::class,
            ])

            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\Filament\Widgets'
            )

            ->widgets([
                AccountWidget::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            ->authMiddleware([
                Authenticate::class,
                IdentifyTenant::class,
            ]);

    }
}

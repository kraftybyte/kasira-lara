<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login as BaseLogin;
use App\Filament\Pages\Settings\GlobalSettings;
use App\Filament\Pages\Settings\TenantSettings;
use App\Models\AppSetting;
use App\Models\Tenant;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        //
    }

    public function panel(Panel $panel): Panel
    {
        // Get app settings for branding
        $appSetting = AppSetting::first();
        $brandLogo = null;
        if ($appSetting?->app_logo) {
            $logo = $appSetting->app_logo;
            // Handle FileUpload JSON format
            if (is_string($logo)) {
                $decoded = json_decode($logo, true);
                if (is_array($decoded) && ! empty($decoded)) {
                    $logo = array_keys($decoded)[0];
                }
            }
            if ($logo) {
                // Path already includes directory, just add storage prefix
                $brandLogo = url('storage/'.$logo);
            }
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')

            ->login(BaseLogin::class)

            // Branding - use app logo if available
            ->brandName($appSetting?->app_name ?? 'Kasira')
            ->brandLogo($brandLogo)
            ->brandLogoHeight('2.5rem')

            // TENANCY - Tenant menu moved to header via custom views
            ->tenant(Tenant::class)

            // Disable global search in sidebar
            ->globalSearch(false)

            // Enable dark mode (no theme switcher)
            ->darkMode(false)
            ->themeSwitcher(false)

            // Plugins - disable Shield's navigation since we use RoleResource
            ->plugin(FilamentShieldPlugin::make()
                ->scopeToTenant(false)
                ->registerNavigation(false))

            // Brand primary color
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
                GlobalSettings::class,
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

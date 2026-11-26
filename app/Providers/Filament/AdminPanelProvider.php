<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login as BaseLogin;
use App\Filament\Pages\Settings\GlobalSettings;
use App\Filament\Pages\Settings\TenantSettings;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetAppLocale;
use App\Models\AppSetting;
use App\Models\Tenant;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
        // Lazy-load app settings to avoid DB query during app boot (breaks tests)
        $brandLogo = null;
        $brandFavicon = null;
        $brandName = null;
        try {
            $appSetting = AppSetting::first();
            $brandName = $appSetting?->app_name ?? 'Kasira';
            $brandLogo = $this->storageUrl($appSetting?->app_logo);
            $brandFavicon = $this->storageUrl($appSetting?->app_favicon);
        } catch (\Throwable) {
            // AppSetting table may not exist during migrations or tests
            $brandName = 'Kasira';
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')

            ->login(BaseLogin::class)

            // Branding - use app logo if available
            ->brandName($brandName ?? 'Kasira')
            ->brandLogo($brandLogo)
            ->favicon($brandFavicon ?? asset('favicon.ico'))
            ->brandLogoHeight('2.5rem')

            // TENANCY - Tenant menu moved to header via custom views
            ->tenant(Tenant::class)

            // Disable global search in sidebar
            ->globalSearch(false)

            ->navigationGroups(['Penjualan', 'Produk & Stok', 'Laporan', 'Pengaturan'])

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
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('6rem')

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
                SetAppLocale::class,
            ])

            ->authMiddleware([
                Authenticate::class,
                IdentifyTenant::class,
            ]);
    }

    /**
     * Public URL of an uploaded file; older uploads were stored as a JSON map keyed by path.
     */
    private function storageUrl(mixed $path): ?string
    {
        if (is_string($path) && is_array($decoded = json_decode($path, true)) && $decoded !== []) {
            $path = array_key_first($decoded);
        }

        return is_string($path) && $path !== '' ? url('storage/'.$path) : null;
    }
}

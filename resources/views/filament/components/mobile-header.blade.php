<div>
    {{-- Mobile Header (only visible on mobile < 768px) --}}
    <div id="mobile-header" class="mobile-header lg:hidden fixed top-0 left-0 right-0 z-60 bg-white border-b border-gray-200" style="margin: 0; padding: 0;">
        <div class="flex items-center justify-between px-3 sm:px-4 h-12" style="margin: 0;">
            {{-- Brand Logo --}}
            <div class="flex items-center gap-2">
                @if (filament()->getBrandLogo())
                    <img src="{{ filament()->getBrandLogo() }}" alt="{{ filament()->getBrandName() }}" class="h-6 w-auto">
                @else
                    <span class="text-base font-bold text-gray-900">{{ filament()->getBrandName() }}</span>
                @endif
            </div>

            {{-- Menu Toggle Button --}}
            <button
                type="button"
                id="mobile-menu-toggle"
                class="p-2.5 sm:p-2 rounded-lg hover:bg-gray-100 transition-colors active:bg-gray-200"
            >
                <x-heroicon-o-bars-3 class="h-5 w-5 text-gray-600" />
            </button>
        </div>
    </div>

    {{-- Mobile Menu Overlay --}}
    <div
        id="mobile-menu-overlay"
        class="lg:hidden fixed inset-0 z-70 bg-black/50 backdrop-blur-sm transition-opacity duration-200"
        style="display: none; opacity: 0;"
    ></div>

    {{-- Mobile Menu Panel (Slide from left) --}}
    <div
        id="mobile-menu-panel"
        class="lg:hidden fixed top-0 left-0 bottom-0 z-80 w-64 sm:w-72 bg-white shadow-2xl transition-transform duration-300 -translate-x-full"
        style="display: block;"
    >
        {{-- Menu Header --}}
        <div class="flex items-center justify-between px-4 h-14 border-b border-gray-200">
            <div class="flex items-center gap-3">
                @if (filament()->getBrandLogo())
                    <img src="{{ filament()->getBrandLogo() }}" alt="{{ filament()->getBrandName() }}" class="h-7 w-auto">
                @else
                    <span class="text-base font-bold text-gray-900">{{ filament()->getBrandName() }}</span>
                @endif
            </div>
            <button
                type="button"
                id="mobile-menu-close"
                class="p-2.5 rounded-lg text-gray-600 hover:bg-gray-100 active:bg-gray-200 min-w-11 min-h-11 flex items-center justify-center"
            >
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Tenant Info --}}
        @if(filament()->hasTenancy() && filament()->hasTenantMenu())
            @php
                $tenant = filament()->getTenant();
            @endphp
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($tenant?->name ?? 'T', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $tenant?->name ?? 'Pilih Tenant' }}</p>
                        <p class="text-xs text-gray-500">Tenant Aktif</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Items --}}
        <nav class="flex-1 overflow-y-auto py-2">
            @php
                $tenant = filament()->getTenant();
                $tenantId = $tenant?->id ?? '';

                $navItems = [
                    ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'url' => $tenantId ? "/admin/{$tenantId}" : '/admin'],
                    ['label' => 'POS', 'icon' => 'heroicon-o-shopping-cart', 'url' => $tenantId ? "/admin/{$tenantId}/pos" : '/admin/pos'],
                    ['label' => 'Kitchen', 'icon' => 'heroicon-o-fire', 'url' => $tenantId ? "/admin/{$tenantId}/kitchen" : '/admin/kitchen'],
                    ['label' => 'Meja', 'icon' => 'heroicon-o-table-cells', 'url' => $tenantId ? "/admin/{$tenantId}/tables" : '/admin/tables'],
                    ['label' => 'Products', 'icon' => 'heroicon-o-cube', 'url' => $tenantId ? "/admin/{$tenantId}/products" : '/admin/products'],
                    ['label' => 'Categories', 'icon' => 'heroicon-o-tag', 'url' => $tenantId ? "/admin/{$tenantId}/categories" : '/admin/categories'],
                    ['label' => 'Customers', 'icon' => 'heroicon-o-users', 'url' => $tenantId ? "/admin/{$tenantId}/customers" : '/admin/customers'],
                    ['label' => 'Sales', 'icon' => 'heroicon-o-currency-dollar', 'url' => $tenantId ? "/admin/{$tenantId}/sales" : '/admin/sales'],
                    ['label' => 'Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => '/admin/settings'],
                ];
            @endphp

            @foreach($navItems as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="flex items-center gap-3 mx-2 px-3 py-3 rounded-lg text-sm font-medium transition-colors mobile-nav-item min-h-12"
                    @if(request()->is(str_replace('/', '\\/', ltrim($item['url'], '/')))
                        || request()->is(str_replace('/', '\\/', ltrim($item['url'], '/')) . '/*'))
                        style="background: rgba(239, 68, 68, 0.1); color: #dc2626;"
                    @else
                        style="color: #374151;"
                    @endif
                >
                    <x-dynamic-component :component="$item['icon']" class="h-5 w-5 shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Footer Actions --}}
        <div class="border-t border-gray-200">
            {{-- Logout --}}
            <div class="p-3">
                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex items-center gap-2 w-full px-3 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors min-h-12"
                    >
                        <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5 shrink-0" />
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var overlay = document.getElementById('mobile-menu-overlay');
            var panel = document.getElementById('mobile-menu-panel');
            var toggle = document.getElementById('mobile-menu-toggle');
            var closeBtn = document.getElementById('mobile-menu-close');

            function openMenu() {
                overlay.style.display = 'block';
                panel.style.display = 'block';
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(function() {
                    overlay.style.opacity = '1';
                    panel.classList.remove('-translate-x-full');
                });
            }

            function closeMenu() {
                overlay.style.opacity = '0';
                panel.classList.add('-translate-x-full');
                setTimeout(function() {
                    overlay.style.display = 'none';
                    panel.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }

            if (toggle) toggle.addEventListener('click', openMenu);
            if (closeBtn) closeBtn.addEventListener('click', closeMenu);
            if (overlay) overlay.addEventListener('click', closeMenu);

            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && panel.style.display !== 'none') {
                    closeMenu();
                }
            });

            document.querySelectorAll('.mobile-nav-item').forEach(function(item) {
                item.addEventListener('click', closeMenu);
            });

            if (window.Livewire) {
                Livewire.hook('navigated', function() {
                    closeMenu();
                });
            }
        })();
    </script>
</div>

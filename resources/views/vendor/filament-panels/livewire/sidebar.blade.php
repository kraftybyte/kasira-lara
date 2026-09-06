<div id="fi-main-sidebar" class="fi-sidebar fi-main-sidebar" style="width: 80px; min-width: 80px;">
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

    <style>
        .fi-sidebar-nav-item {
            margin-bottom: 4px !important;
            width: 100%;
        }
        .fi-sidebar-nav-item-button {
            border-radius: 14px !important;
            padding: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            border: none !important;
            transition: all 0.2s ease !important;
            width: 100% !important;
        }
        .fi-sidebar-nav-item-button:hover {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
        }
        .fi-sidebar-nav-item-button:hover svg {
            color: white !important;
        }
        .fi-sidebar-nav-item.fi-active > .fi-sidebar-nav-item-button {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
        }
        .fi-sidebar-nav-item.fi-active > .fi-sidebar-nav-item-button svg {
            color: white !important;
        }

        /* Mobile responsive - smaller but visible */
        @media (max-width: 1024px) {
            #fi-main-sidebar {
                width: 64px !important;
                min-width: 64px !important;
            }
            .fi-sidebar-nav-item-button {
                padding: 10px 8px !important;
            }
        }
    </style>

    <div class="fi-sidebar-header-ctn">
        <header class="fi-sidebar-header">
            <div class="fi-sidebar-logo-only">
                @if ($homeUrl = filament()->getHomeUrl())
                    <a {{ \Filament\Support\generate_href_html($homeUrl) }} class="fi-sidebar-logo-link">
                        <x-filament-panels::logo />
                    </a>
                @else
                    <x-filament-panels::logo />
                @endif
            </div>
        </header>
    </div>

    <nav aria-label="{{ __('filament-panels::layout.navigation.label') }}" class="fi-sidebar-nav">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

        <ul class="fi-sidebar-nav-groups">
            @foreach (filament()->getNavigation() as $group)
                @php
                    $groupItems = $group->getItems();
                @endphp

                @foreach ($groupItems as $item)
                    @php
                        $itemUrl = $item->getUrl();
                        $isActive = $item->isActive();
                        $activeIcon = $item->getActiveIcon();
                        $displayIcon = $isActive && $activeIcon ? $activeIcon : $item->getIcon();
                    @endphp

                    <li class="fi-sidebar-nav-item">
                        <a
                            href="{{ $itemUrl }}"
                            class="fi-sidebar-nav-item-button {{ $isActive ? 'fi-active' : '' }}"
                            @if($isActive) aria-current="page" @endif
                        >
                            @if ($displayIcon)
                                {{ \Filament\Support\generate_icon_html($displayIcon) }}
                            @endif
                        </a>
                    </li>
                @endforeach
            @endforeach
        </ul>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
    </nav>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
</div>

<?php

namespace App\Providers;

use App\Filament\Backend\Pages\Auth\LoginPage;
use App\Filament\Backend\Pages\Profile;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class BackendPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->configurePanel($panel)
            ->id('backend')
            ->path('backend')
            ->discoverResources(in: app_path('Filament/Backend/Resources'), for: 'App\Filament\Backend\Resources')
            ->discoverPages(in: app_path('Filament/Backend/Pages'), for: 'App\Filament\Backend\Pages')
            ->discoverClusters(in: app_path('Filament/Backend/Clusters'), for: 'App\Filament\Backend\Clusters')
            ->discoverWidgets(in: app_path('Filament/Backend/Widgets'), for: 'App\Filament\Backend\Widgets')
            ->authGuard('backend')
            ->brandName('超管后台')
            ->colors([
                'primary' => Color::hex('#0eb0c9'),
            ])
            ->domain(config('custom.domains.backend_domain'))
            ->login(LoginPage::class)
            ->profile(Profile::class, false)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('@livewire(\'filament.homepage\')'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('@livewire(\'filament.clear-cache\')'),
            );
    }
}

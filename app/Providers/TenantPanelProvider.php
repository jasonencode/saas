<?php

namespace App\Providers;

use App\Filament\Tenant\Pages\Auth\LoginPage;
use App\Filament\Tenant\Pages\Profile;
use App\Filament\Tenant\Pages\TenantProfile;
use App\Http\Middleware\EnsureTenantNotExpired;
use App\Models\System\Tenant;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;

class TenantPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->configurePanel($panel)
            ->id('tenant')
            ->path('tenant')
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\Filament\Tenant\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\Filament\Tenant\Pages')
            ->discoverClusters(in: app_path('Filament/Tenant/Clusters'), for: 'App\Filament\Tenant\Clusters')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\Filament\Tenant\Widgets')
            ->tenantMiddleware([
                EnsureTenantNotExpired::class,
            ])
            ->authGuard('tenant')
            ->brandName('管理平台')
            ->colors([
                'primary' => Color::hex('#ffc107'),
            ])
            ->domain(config('custom.domains.tenant_domain'))
            ->login(LoginPage::class)
            ->profile(Profile::class)
            ->tenantProfile(TenantProfile::class)
            ->tenant(Tenant::class, 'slug')
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('livewire.filament.print-script')->render(),
            );
    }
}

<?php

namespace App\Providers;

use App\Extensions\Module\ModulesPlugin;
use App\Filament\Tenant\Pages\Auth\EditProfile;
use App\Filament\Tenant\Pages\Auth\Login;
use App\Filament\Tenant\Pages\Auth\TenantProfile;
use App\Models\Tenant;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

class TenantPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            ->default()
            ->login(Login::class)
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverClusters(in: app_path('Filament/Tenant/Clusters'), for: 'App\\Filament\\Tenant\\Clusters')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->profile(EditProfile::class)
            ->tenantProfile(TenantProfile::class)
            ->tenant(Tenant::class, 'slug')
            ->authGuard('tenant')
            ->brandName('系统管理平台')
            ->databaseNotifications()
            ->spa()
            ->maxContentWidth(MaxWidth::Full)
            ->breadcrumbs(false)
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->font('')
            ->databaseTransactions()
            ->plugins($this->getPlugins());
    }

    protected function getPlugins(): array
    {
        return [
            ModulesPlugin::make(),
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('38%')
                ->formPanelPosition('left')
                ->emptyPanelBackgroundImageOpacity('90%'),
            FilamentBackgroundsPlugin::make()
                ->remember(1)
                ->showAttribution(false)
                ->imageProvider(
                    CuratedBySwis::make()
                ),
        ];
    }
}

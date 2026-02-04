<?php

namespace App\Providers;

use App\Filament\Tenant\Pages\Auth\LoginPage;
use App\Filament\Tenant\Pages\Profile;
use App\Filament\Tenant\Pages\TenantProfile;
use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('tenant')
            ->path('tenant')
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\Filament\Tenant\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\Filament\Tenant\Pages')
            ->discoverClusters(in: app_path('Filament/Tenant/Clusters'), for: 'App\Filament\Tenant\Clusters')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\Filament\Tenant\Widgets')
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
            ->authGuard('tenant')
            ->brandName('管理平台')
            ->breadcrumbs(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->databaseTransactions()
            ->domain(config('custom.domains.tenant_domain'))
            ->font(null)
            ->login(LoginPage::class)
            ->maxContentWidth(Width::Full)
            ->plugins($this->getPlugins())
            ->profile(Profile::class)
            ->spa()
            ->tenantProfile(TenantProfile::class)
            ->tenant(Tenant::class, 'slug')
            ->topNavigation()
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/filament/backend/theme.css');
    }
}

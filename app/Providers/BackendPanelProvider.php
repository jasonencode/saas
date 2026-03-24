<?php

namespace App\Providers;

use App\Filament\Backend\Pages\Auth\LoginPage;
use App\Filament\Backend\Pages\Profile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BackendPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('backend')
            ->default()
            ->path('backend')
            ->discoverResources(in: app_path('Filament/Backend/Resources'), for: 'App\Filament\Backend\Resources')
            ->discoverPages(in: app_path('Filament/Backend/Pages'), for: 'App\Filament\Backend\Pages')
            ->discoverClusters(in: app_path('Filament/Backend/Clusters'), for: 'App\Filament\Backend\Clusters')
            ->discoverWidgets(in: app_path('Filament/Backend/Widgets'), for: 'App\Filament\Backend\Widgets')
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
            ->authGuard('backend')
            ->brandName('超管后台')
            ->breadcrumbs(false)
            ->colors([
                'primary' => Color::hex('#0eb0c9'),
            ])
            ->databaseNotifications()
            ->databaseTransactions()
            ->domain(config('custom.domains.backend_domain'))
            ->font(null)
            ->login(LoginPage::class)
            ->profile(Profile::class, false)
            ->maxContentWidth(Width::Full)
            ->plugins($this->getPlugins())
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('@livewire(\'clear-cache\')'),
            )
            ->spa()
            ->topNavigation()
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/filament/backend/theme.css')
            ->resourceEditPageRedirect('index')
            ->resourceCreatePageRedirect('index')
            ->strictAuthorization(false);
    }
}

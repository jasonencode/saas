<?php

namespace App\Providers;

use App\Filament\Backend\Pages\Auth\EditProfile;
use App\Filament\Backend\Pages\Auth\Login;
use App\Filament\Backend\Pages\Dashboard;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mvenghaus\FilamentScheduleMonitor\FilamentPlugin;

class BackendPanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('backend')
            ->path('backend')
            ->discoverResources(in: app_path('Filament/Backend/Resources'), for: 'App\\Filament\\Backend\\Resources')
            ->discoverPages(in: app_path('Filament/Backend/Pages'), for: 'App\\Filament\\Backend\\Pages')
            ->discoverWidgets(in: app_path('Filament/Backend/Widgets'), for: 'App\\Filament\\Backend\\Widgets')
            ->discoverClusters(in: app_path('Filament/Backend/Clusters'), for: 'App\\Filament\\Backend\\Clusters')
            ->theme(asset('css/filament/backend/theme.css'))
            ->topNavigation()
            ->colors([
                'primary' => Color::hex('#0eb0c9'),
            ])
            ->pages([
                Dashboard::class,
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
            ->login(Login::class)
            ->profile(EditProfile::class)
            ->authGuard('backend')
            ->brandName('超管后台')
            ->databaseNotifications()
            ->spa()
            ->spaUrlExceptions([
                '*/backend/contents/contents/create',
            ])
            ->domain(config('custom.domains.backend_domain'))
            ->maxContentWidth(MaxWidth::Full)
            ->breadcrumbs(false)
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->font('')
            ->navigationItems([
                NavigationItem::make('队列监控')
                    ->url(url: '/backend/horizon', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('扩展')
                    ->visible(fn() => Auth::id() == 1)
                    ->sort(100),
            ])
            ->databaseTransactions()
            ->plugins($this->getPlugins())
            ->plugin(FilamentLogViewerPlugin::make())
            ->plugin(FilamentPlugin::make());
    }
}

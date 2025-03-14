<?php

namespace App\Providers;

use App\Extensions\Module\ModulesPlugin;
use App\Filament\Backend\Pages\Auth\EditProfile;
use App\Filament\Backend\Pages\Auth\Login;
use App\Filament\Backend\Pages\Dashboard;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mvenghaus\FilamentScheduleMonitor\FilamentPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                ...collect(Color::all())->forget(['slate', 'gray', 'zinc', 'neutral', 'stone'])->toArray(),
                'primary' => Color::hex('#45B39D'),
                'secondary' => Color::hex('#F1948A'),
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
            ->maxContentWidth(MaxWidth::Full)
            ->breadcrumbs(false)
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->font('')
            ->navigationItems([
                NavigationItem::make('队列监控')
                    ->url(url: '/admin/horizon', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('扩展')
                    ->sort(100),
            ])
            ->databaseTransactions()
            ->plugins($this->getPlugins());
    }

    protected function getPlugins(): array
    {
        return [
            ModulesPlugin::make(),
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('40%')
                ->emptyPanelBackgroundImageOpacity('90%'),
            FilamentBackgroundsPlugin::make()
                ->remember(1)
                ->showAttribution(false)
                ->imageProvider(
                    CuratedBySwis::make()
                ),
            FilamentPlugin::make(),
        ];
    }
}

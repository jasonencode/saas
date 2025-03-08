<?php

namespace App\Providers;

use App\Admin\Pages\Auth\EditProfile;
use App\Admin\Pages\Auth\Login;
use App\Admin\Pages\Dashboard;
use App\Extensions\Module\ModulesPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\View\Components\Modal;
use Filament\Tables\Table;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mvenghaus\FilamentScheduleMonitor\FilamentPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->configureModal();
        $this->configureTable();

        Export::polymorphicUserRelationship();
        Import::polymorphicUserRelationship();
        # action 有 auth 中间件，需要修改，否则经常会报login路由不存在
        app(Router::class)
            ->removeMiddlewareFromGroup('filament.actions', 'auth')
            ->prependMiddlewareToGroup('filament.actions', 'web')
            ->prependMiddlewareToGroup('filament.actions', 'auth:admin');
    }

    protected function configureModal(): void
    {
        Modal::closedByClickingAway(false);
        Modal::closedByEscaping();
        Modal::autofocus(false);
    }

    protected function configureTable(): void
    {
        Table::configureUsing(function(Table $table): void {
            $table->striped()
                ->extremePaginationLinks()
                ->persistSearchInSession()
                ->selectCurrentPageOnly();
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->discoverResources(in: app_path('Admin/Resources'), for: 'App\\Admin\\Resources')
            ->discoverPages(in: app_path('Admin/Pages'), for: 'App\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Admin/Widgets'), for: 'App\\Admin\\Widgets')
            ->discoverClusters(in: app_path('Admin/Clusters'), for: 'App\\Admin\\Clusters')
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
            ->authGuard('admin')
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

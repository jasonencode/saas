<?php

namespace App\Providers;

use App\Backend\Pages\Auth\EditProfile;
use App\Backend\Pages\Auth\EditTeamProfile;
use App\Backend\Pages\Auth\Login;
use App\Extensions\Module\ModulesPlugin;
use App\Models\Team;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

class BackendPanelProvider extends PanelProvider
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
            ->id('backend')
            ->path('backend')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Backend/Resources'), for: 'App\\Backend\\Resources')
            ->discoverPages(in: app_path('Backend/Pages'), for: 'App\\Backend\\Pages')
            ->discoverClusters(in: app_path('Backend/Clusters'), for: 'App\\Backend\\Clusters')
            ->discoverWidgets(in: app_path('Backend/Widgets'), for: 'App\\Backend\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
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
            ->tenantProfile(EditTeamProfile::class)
            ->tenant(Team::class, 'slug')
            ->authGuard('staffer')
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

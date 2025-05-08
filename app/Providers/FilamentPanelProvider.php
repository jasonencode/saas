<?php

namespace App\Providers;

use Coolsam\Modules\ModulesPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\PanelProvider;
use Filament\Support\View\Components\Modal;
use Filament\Tables\Table;
use Illuminate\Routing\Router;
use Mvenghaus\FilamentScheduleMonitor\FilamentPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

abstract class FilamentPanelProvider extends PanelProvider
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

    protected function getPlugins(): array
    {
        return [
            FilamentPlugin::make(),
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('40%')
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

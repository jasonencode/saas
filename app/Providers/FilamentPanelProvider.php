<?php

namespace App\Providers;

use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\PanelProvider;
use Filament\Support\View\Components\Modal;
use Filament\Tables\Table;
use Illuminate\Routing\Router;

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
}

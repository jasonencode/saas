<?php

namespace App\Providers;

use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Modal;
use Filament\Tables\Table;
use Illuminate\Routing\Router;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\CuratedBySwis;

abstract class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->configureModal();
        $this->configureTable();
        $this->configureColors();

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

    /**
     * 没注册 gray 注册了gray页面就变蓝了
     *
     * @return void
     */
    public function configureColors(): void
    {
        FilamentColor::register([
            'slate' => Color::Slate,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
        ]);
    }

    protected function configureTable(): void
    {
        Table::configureUsing(static function(Table $table): void {
            $table->striped()
                ->extremePaginationLinks()
                ->persistSearchInSession()
                ->selectCurrentPageOnly();
        });
    }

    protected function getPlugins(): array
    {
        return [
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

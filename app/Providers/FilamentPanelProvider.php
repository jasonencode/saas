<?php

namespace App\Providers;

use Coolsam\Modules\ModulesPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Routing\Router;

abstract class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->configureModal();
        $this->configureTable();
        $this->configureForm();
        $this->configureColors();

        Export::polymorphicUserRelationship();
        Import::polymorphicUserRelationship();
        // action 有 auth 中间件，需要修改，否则经常会报login路由不存在
        app(Router::class)
            ->removeMiddlewareFromGroup('filament.actions', 'auth')
            ->prependMiddlewareToGroup('filament.actions', 'web')
            ->prependMiddlewareToGroup('filament.actions', 'auth:admin');
    }

    protected function configureModal(): void
    {
        ModalComponent::closedByClickingAway(false);
        ModalComponent::closedByEscaping();
        ModalComponent::autofocus(false);
    }

    /**
     * 没注册 gray 注册了gray页面就变蓝了
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

        SelectFilter::configureUsing(static function(SelectFilter $filter): void {
            $filter->native(false);
        });
    }

    protected function configureForm(): void
    {
        Toggle::configureUsing(static function(Toggle $toggle): void {
            $toggle->onIcon(Heroicon::Bolt)
                ->offIcon(Heroicon::XMark)
                ->default(true)
                ->inline(false)
                ->inlineLabel(false);
        });

        Radio::configureUsing(static function(Radio $radio): void {
            $radio->inline(false)
                ->inlineLabel(false);
        });

        Select::configureUsing(static function(Select $select): void {
            $select->native(false);
        });

        DatePicker::configureUsing(static function(DatePicker $datePicker): void {
            $datePicker->native(false);
        });

        DateTimePicker::configureUsing(static function(DateTimePicker $dateTimePicker): void {
            $dateTimePicker->native(false);
        });
    }

    protected function getPlugins(): array
    {
        return [
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('40%')
//                ->emptyPanelBackgroundImageOpacity('90%')
                ->emptyPanelBackgroundImageUrl($this->getImage()),
            ModulesPlugin::make(),
        ];
    }

    protected function getImage(): string
    {
        // return asset('images/tenant-auth-background.svg');
        return asset('images/backend-auth-background.jpg');
    }
}

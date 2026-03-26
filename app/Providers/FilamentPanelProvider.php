<?php

namespace App\Providers;

use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Filament\Infolists;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Routing\Router;
use Sanzgrapher\DraggableModal\DraggableModalPlugin;

abstract class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Export::polymorphicUserRelationship();
        Import::polymorphicUserRelationship();

        // 优化：Action 路由默认使用 auth 中间件（通常是 web guard）。
        // 这里修改 filament.actions 中间件组，使其支持 backend 和 tenant guard，
        // 避免在非 web guard 下报 login 路由不存在或 401 错误。
        // 将 auth:admin 修正为 auth:backend,tenant 以支持多面板。
        app(Router::class)
            ->removeMiddlewareFromGroup('filament.actions', 'auth')
            ->prependMiddlewareToGroup('filament.actions', 'web')
            ->prependMiddlewareToGroup('filament.actions', 'auth:backend,tenant');

        $this->configureColors();
        $this->configureDefaults();
    }

    /**
     * 注册所有可用颜色，方便开发时直接使用
     */
    public function configureColors(): void
    {
        FilamentColor::register([
            'slate' => Color::Slate,     // 石板色
            'zinc' => Color::Zinc,       // 锌色
            'neutral' => Color::Neutral, // 中性色
            'stone' => Color::Stone,     // 石色
            'red' => Color::Red,         // 红色
            'orange' => Color::Orange,   // 橙色
            'amber' => Color::Amber,     // 琥珀色
            'yellow' => Color::Yellow,   // 黄色
            'lime' => Color::Lime,       // 柠檬色
            'green' => Color::Green,     // 绿色
            'emerald' => Color::Emerald, // 翡翠色
            'teal' => Color::Teal,       // 蓝绿色
            'cyan' => Color::Cyan,       // 青色
            'sky' => Color::Sky,         // 天蓝色
            'blue' => Color::Blue,       // 蓝色
            'indigo' => Color::Indigo,   // 靛蓝色
            'violet' => Color::Violet,   // 紫罗兰色
            'purple' => Color::Purple,   // 紫色
            'fuchsia' => Color::Fuchsia, // 紫红色
            'pink' => Color::Pink,       // 粉红色
            'rose' => Color::Rose,       // 玫瑰色
        ]);
    }

    /**
     * 统一配置组件默认行为
     */
    protected function configureDefaults(): void
    {
        $this->configureOverlays();
        $this->configureTables();
        $this->configureActions();
        $this->configureForms();
        $this->configureInfolists();
    }

    protected function configureOverlays(): void
    {
        ModalComponent::closedByClickingAway(false);
        ModalComponent::closedByEscaping();
        ModalComponent::autofocus(false);
    }

    protected function configureTables(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table->striped()
                ->extremePaginationLinks()
                ->selectCurrentPageOnly();
        });

        // 筛选器默认配置
        Tables\Filters\SelectFilter::configureUsing(static fn (Tables\Filters\SelectFilter $filter) => $filter->native(false));
        Tables\Filters\TrashedFilter::configureUsing(static fn (Tables\Filters\TrashedFilter $filter) => $filter->native(false));
        Tables\Filters\TernaryFilter::configureUsing(static fn (Tables\Filters\TernaryFilter $filter) => $filter->native(false));
    }

    protected function configureActions(): void
    {
        Actions\BulkAction::configureUsing(static function (Actions\BulkAction $action) {
            $action->deselectRecordsAfterCompletion();
        });

        Actions\CreateAction::configureUsing(static function (Actions\CreateAction $action) {
            $action->icon(Heroicon::Plus);
        });
    }

    protected function configureForms(): void
    {
        // 注意：全局设置 visibility 为 public 可能存在安全风险，请确保敏感文件上传时覆盖此设置。
        Forms\Components\FileUpload::configureUsing(static function (Forms\Components\FileUpload $fileUpload) {
            $fileUpload->reorderable()
                ->appendFiles()
                ->openable()
                ->downloadable()
                ->visibility('public');
        });

        Forms\Components\Select::configureUsing(static fn (Forms\Components\Select $select) => $select->native(false));

        Forms\Components\DatePicker::configureUsing(static function (Forms\Components\DatePicker $datePicker) {
            $datePicker->native(false)
                ->displayFormat('Y-m-d')
                ->closeOnDateSelection();
        });

        Forms\Components\DateTimePicker::configureUsing(static function (Forms\Components\DateTimePicker $dateTimePicker) {
            $dateTimePicker->native(false)
                ->displayFormat('Y-m-d H:i:s')
                ->closeOnDateSelection();
        });

        Forms\Components\RichEditor::configureUsing(static function (Forms\Components\RichEditor $editor) {
            $editor->resizableImages();
        });

        Forms\Components\Toggle::configureUsing(static function (Forms\Components\Toggle $toggle) {
            $toggle->inline(false)
                ->inlineLabel(false)
                ->default(true);
        });

        Forms\Components\Radio::configureUsing(static function (Forms\Components\Radio $radio) {
            $radio->inline()
                ->inlineLabel(false);
        });
    }

    protected function configureInfolists(): void
    {
        Tables\Columns\ImageColumn::configureUsing(static function (Tables\Columns\ImageColumn $column) {
            $column->checkFileExistence(false)
                ->visibility('public');
        });

        Infolists\Components\ImageEntry::configureUsing(static function (Infolists\Components\ImageEntry $imageEntry) {
            $imageEntry->checkFileExistence(false)
                ->visibility('public');
        });
    }

    protected function getPlugins(): array
    {
        return [
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('40%')
                ->emptyPanelBackgroundImageUrl($this->getImage()),
            DraggableModalPlugin::make(),
        ];
    }

    protected function getImage(): string
    {
        return asset('images/backend-auth-background.jpg');
    }
}

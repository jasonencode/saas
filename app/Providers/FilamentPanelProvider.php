<?php

namespace App\Providers;

use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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
        SelectFilter::configureUsing(static fn(SelectFilter $filter) => $filter->native(false));
        TrashedFilter::configureUsing(static fn(TrashedFilter $filter) => $filter->native(false));
        TernaryFilter::configureUsing(static fn(TernaryFilter $filter) => $filter->native(false));
    }

    protected function configureActions(): void
    {
        BulkAction::configureUsing(static function (BulkAction $action) {
            $action->deselectRecordsAfterCompletion();
        });

        CreateAction::configureUsing(static function (CreateAction $action) {
            $action->icon(Heroicon::Plus);
        });
    }

    protected function configureForms(): void
    {
        // 注意：全局设置 visibility 为 public 可能存在安全风险，请确保敏感文件上传时覆盖此设置。
        FileUpload::configureUsing(static function (FileUpload $fileUpload) {
            $fileUpload->reorderable()
                ->appendFiles()
                ->openable()
                ->downloadable()
                ->visibility('public');
        });

        Select::configureUsing(static fn(Select $select) => $select->native(false));

        DatePicker::configureUsing(static function (DatePicker $datePicker) {
            $datePicker->native(false)
                ->displayFormat('Y-m-d')
                ->closeOnDateSelection();
        });

        DateTimePicker::configureUsing(static function (DateTimePicker $dateTimePicker) {
            $dateTimePicker->native(false)
                ->displayFormat('Y-m-d H:i:s')
                ->closeOnDateSelection();
        });

        RichEditor::configureUsing(static function (RichEditor $editor) {
            $editor->resizableImages();
        });

        Toggle::configureUsing(static function (Toggle $toggle) {
            $toggle->inline(false)
                ->inlineLabel(false)
                ->default(true);
        });

        Radio::configureUsing(static function (Radio $radio) {
            $radio->inline()
                ->inlineLabel(false);
        });
    }

    protected function configureInfolists(): void
    {
        ImageColumn::configureUsing(static function (ImageColumn $column) {
            $column->checkFileExistence(false)
                ->visibility('public');
        });

        ImageEntry::configureUsing(static function (ImageEntry $imageEntry) {
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

<?php

namespace App\Providers;

use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Actions;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Filament\Infolists;
use Filament\PanelProvider;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Routing\Router;
use Sanzgrapher\DraggableModal\DraggableModalPlugin;

abstract class FilamentPanelProvider extends PanelProvider
{
    /**
     * 引导面板服务
     */
    public function boot(): void
    {
        $this->configureTimezone();
        $this->configurePolymorphicRelationships();
        $this->configureMiddleware();
        $this->configureColors();
        $this->configureDefaults();
    }

    /**
     * 配置时区
     */
    protected function configureTimezone(): void
    {
        FilamentTimezone::set('Asia/Shanghai');
    }

    /**
     * 配置多态关联关系
     */
    protected function configurePolymorphicRelationships(): void
    {
        Export::polymorphicUserRelationship();
        Import::polymorphicUserRelationship();
    }

    /**
     * 配置中间件
     *
     * 避免在非 web guard 下报 login 路由不存在或 401 错误。
     */
    protected function configureMiddleware(): void
    {
        app(Router::class)->middlewareGroup('filament.actions', ['web', 'auth:backend,tenant']);
    }

    /**
     * 注册所有可用颜色，方便开发时直接使用
     */
    protected function configureColors(): void
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
        $this->configureTableFilters();
        $this->configureTableColumns();
        $this->configureActions();
        $this->configureForms();
        $this->configureInfolists();
    }

    /**
     * 配置弹窗默认行为
     */
    protected function configureOverlays(): void
    {
        ModalComponent::closedByClickingAway(false);
        ModalComponent::closedByEscaping();
        ModalComponent::autofocus(false);
    }

    /**
     * 配置表格默认行为
     */
    protected function configureTables(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table->striped()
                ->extremePaginationLinks()
                ->selectCurrentPageOnly()
                ->defaultDateTimeDisplayFormat('Y-m-d H:i:s')
                ->defaultDateDisplayFormat('Y-m-d')
                ->defaultIsoDateTimeDisplayFormat('Y-m-d H:i:s')
                ->defaultIsoDateDisplayFormat('Y-m-d');
        });
    }

    /**
     * 配置表格筛选器默认行为
     */
    protected function configureTableFilters(): void
    {
        Tables\Filters\SelectFilter::configureUsing(
            static fn (Tables\Filters\SelectFilter $filter) => $filter->native(false)
        );

        Tables\Filters\TrashedFilter::configureUsing(
            static fn (Tables\Filters\TrashedFilter $filter) => $filter->native(false)
        );

        Tables\Filters\TernaryFilter::configureUsing(
            static fn (Tables\Filters\TernaryFilter $filter) => $filter->native(false)
        );
    }

    /**
     * 配置表格列默认行为
     */
    protected function configureTableColumns(): void
    {
        Tables\Columns\ImageColumn::configureUsing(static function (Tables\Columns\ImageColumn $column) {
            $column->checkFileExistence(false)
                ->visibility('public');
        });

        Actions\ActionGroup::configureUsing(
            static fn (Actions\ActionGroup $group) => $group->label('操作')->link()
        );
    }

    /**
     * 配置操作默认行为
     */
    protected function configureActions(): void
    {
        Actions\BulkAction::configureUsing(static function (Actions\BulkAction $action) {
            $action->deselectRecordsAfterCompletion();
        });

        Actions\CreateAction::configureUsing(static function (Actions\CreateAction $action) {
            $action->icon(Heroicon::Plus);
        });
    }

    /**
     * 配置表单组件默认行为
     */
    protected function configureForms(): void
    {
        $this->configureFileUpload();
        $this->configureSelect();
        $this->configureDatePickers();
        $this->configureRichEditor();
        $this->configureToggleAndRadio();
    }

    /**
     * 配置文件上传默认行为
     *
     * 注意：全局设置 visibility 为 public 可能存在安全风险，请确保敏感文件上传时覆盖此设置。
     */
    protected function configureFileUpload(): void
    {
        Forms\Components\FileUpload::configureUsing(static function (Forms\Components\FileUpload $fileUpload) {
            $fileUpload->reorderable()
                ->appendFiles()
                ->openable()
                ->downloadable()
                ->visibility('public');
        });
    }

    /**
     * 配置下拉选择默认行为
     */
    protected function configureSelect(): void
    {
        Forms\Components\Select::configureUsing(
            static fn (Forms\Components\Select $select) => $select->native(false)
        );
    }

    /**
     * 配置日期时间选择器默认行为
     */
    protected function configureDatePickers(): void
    {
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
    }

    /**
     * 配置富文本编辑器默认行为
     */
    protected function configureRichEditor(): void
    {
        Forms\Components\RichEditor::configureUsing(static function (Forms\Components\RichEditor $editor) {
            $editor->resizableImages()
                ->extraInputAttributes(['style' => 'min-height: 300px']);
        });
    }

    /**
     * 配置开关和单选按钮默认行为
     */
    protected function configureToggleAndRadio(): void
    {
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

    /**
     * 配置信息列表组件默认行为
     */
    protected function configureInfolists(): void
    {
        Schema::configureUsing(static function (Schema $schema) {
            $schema->defaultDateDisplayFormat('Y-m-d')
                ->defaultDateTimeDisplayFormat('Y-m-d H:i:s')
                ->defaultIsoDateDisplayFormat('Y-m-d')
                ->defaultIsoDateTimeDisplayFormat('Y-m-d H:i:s');
        });

        Infolists\Components\ImageEntry::configureUsing(static function (Infolists\Components\ImageEntry $imageEntry) {
            $imageEntry->checkFileExistence(false)
                ->visibility('public');
        });
    }

    /**
     * 获取面板插件列表
     *
     * @return array<int, mixed> 插件数组
     */
    protected function getPlugins(): array
    {
        return [
            AuthUIEnhancerPlugin::make()
                ->formPanelWidth('40%')
                ->emptyPanelBackgroundImageUrl($this->getImage()),
            DraggableModalPlugin::make(),
        ];
    }

    /**
     * 获取登录页背景图片地址
     *
     * @return string 图片 URL
     */
    protected function getImage(): string
    {
        return asset('images/backend-auth-background.jpg');
    }
}

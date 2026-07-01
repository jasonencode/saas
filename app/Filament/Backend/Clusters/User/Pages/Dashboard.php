<?php

namespace App\Filament\Backend\Clusters\User\Pages;

use App\Filament\Backend\Clusters\User\UserCluster;
use App\Filament\Backend\Clusters\User\Widgets\UserStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static ?string $cluster = UserCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = '数据看板';

    protected static ?string $title = '用户数据看板';

    protected static ?int $navigationSort = -1;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->components($this->getWidgetsSchemaComponents($this->getWidgets()));
    }

    public function getColumns(): int
    {
        return 4;
    }

    public function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
        ];
    }
}

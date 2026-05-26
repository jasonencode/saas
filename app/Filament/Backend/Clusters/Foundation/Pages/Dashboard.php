<?php

namespace App\Filament\Backend\Clusters\Foundation\Pages;

use App\Filament\Backend\Clusters\Foundation\FoundationCluster;
use App\Filament\Backend\Clusters\Foundation\Widgets\FoundationStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static ?string $cluster = FoundationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = '基础设施看板';

    protected static ?string $title = '基础设施数据看板';

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
            ->schema($this->getWidgetsSchemaComponents($this->getWidgets()));
    }

    public function getColumns(): int
    {
        return 4;
    }

    public function getWidgets(): array
    {
        return [
            FoundationStatsWidget::class,
        ];
    }
}

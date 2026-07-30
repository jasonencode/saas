<?php

namespace App\Filament\Backend\Clusters\BlockChain\Pages;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Filament\Backend\Clusters\BlockChain\Widgets\BlockChainStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static ?string $cluster = BlockChainCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = '区块链看板';

    protected static ?string $title = '区块链数据看板';

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
        return Schemas\Components\Grid::make($this->getColumns())
            ->components($this->getWidgetsSchemaComponents($this->getWidgets()));
    }

    public function getColumns(): int
    {
        return 4;
    }

    public function getWidgets(): array
    {
        return [
            BlockChainStatsWidget::class,
        ];
    }
}

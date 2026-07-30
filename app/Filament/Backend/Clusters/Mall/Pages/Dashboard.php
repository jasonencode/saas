<?php

namespace App\Filament\Backend\Clusters\Mall\Pages;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Filament\Backend\Clusters\Mall\Widgets\StatsOverview;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '数据看板';

    protected static ?string $title = '数据看板';

    protected static ?int $navigationSort = -2;

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
            StatsOverview::class,
        ];
    }
}

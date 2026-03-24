<?php

namespace App\Filament\Backend\Pages;

use App\Filament\Backend\Widgets;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::Home;

    protected static ?string $navigationLabel = '仪表板';

    protected static ?string $title = '仪表板';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

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

    public function getWidgets(): array
    {
        return [
            Widgets\AccountWidget::class,
            Widgets\SystemInfoWidget::class,
            Widgets\UserOverview::class,
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}

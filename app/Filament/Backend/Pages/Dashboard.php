<?php

namespace App\Filament\Backend\Pages;

use App\Filament\Backend\Widgets\AccountWidget;
use App\Filament\Backend\Widgets\UserOverview;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament-panels::pages.dashboard';

    protected static ?string $navigationIcon = 'heroicon-m-home';

    protected static ?string $navigationLabel = '仪表板';

    protected static ?string $title = '仪表板';

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public function getVisibleWidgets(): array
    {
        return [
            AccountWidget::class,
            UserOverview::class,
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}

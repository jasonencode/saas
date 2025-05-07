<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\NoticeTable;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament-panels::pages.dashboard';

    protected static ?string $title = '仪表板';

    protected static ?string $navigationIcon = 'heroicon-m-home';

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public function getVisibleWidgets(): array
    {
        return [
            NoticeTable::class,
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}

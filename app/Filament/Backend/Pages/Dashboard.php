<?php

namespace App\Filament\Backend\Pages;

use App\Filament\Backend\Widgets\AccountWidget;
use App\Filament\Backend\Widgets\UserOverview;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament-panels::pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
    }

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

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }

    public function getColumns(): int
    {
        return 4;
    }
}

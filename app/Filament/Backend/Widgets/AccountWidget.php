<?php

namespace App\Filament\Backend\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected static string $view = 'filament-panels::widgets.account-widget';
}

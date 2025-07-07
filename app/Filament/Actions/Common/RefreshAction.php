<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;

class RefreshAction
{
    public static function make(): Action
    {
        return Action::make('refresh')
            ->outlined()
            ->icon('heroicon-m-arrow-path')
            ->label('刷新')
            ->alpineClickHandler('window.location.reload();');
    }
}

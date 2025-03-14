<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;

class RefreshAction
{
    public static function make()
    {
        return Action::make('refresh')
            ->outlined()
            ->icon('heroicon-m-arrow-path')
            ->label('刷新')
            ->alpineClickHandler('window.location.reload();');
    }
}

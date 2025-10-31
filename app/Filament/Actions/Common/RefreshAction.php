<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RefreshAction
{
    public static function make(): Action
    {
        return Action::make('refresh')
            ->label('刷新')
            ->icon(Heroicon::OutlinedArrowPath)
            ->action(fn() => self::dispatch('refreshTable')); // 有点问题，不能用呢
    }
}

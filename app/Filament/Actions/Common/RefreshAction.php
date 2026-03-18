<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class RefreshAction
{
    public static function make(): Action
    {
        return Action::make('refresh')
            ->label('刷新')
            ->icon(Heroicon::OutlinedArrowPath)
            ->action(function (Component $livewire) {
                $livewire->dispatch('$refresh');
            });
    }
}

<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class RefreshAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refresh';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('刷新');
        $this->icon(Heroicon::OutlinedArrowPath);

        $this->action(function (Component $livewire): void {
            $livewire->dispatch('$refresh');
        });
    }
}

<?php

namespace App\Filament\Actions\Mall;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class PrintPickingListAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'printPickingList';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('打印分拣单');
        $this->icon(Heroicon::OutlinedPrinter);
    }
}
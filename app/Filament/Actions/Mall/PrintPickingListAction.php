<?php

namespace App\Filament\Actions\Mall;

use Filament\Actions\Action;

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
    }
}
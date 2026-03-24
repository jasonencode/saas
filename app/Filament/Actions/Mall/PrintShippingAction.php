<?php

namespace App\Filament\Actions\Mall;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class PrintShippingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'printShipping';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('打印发货单');
        $this->icon(Heroicon::OutlinedPrinter);
    }
}
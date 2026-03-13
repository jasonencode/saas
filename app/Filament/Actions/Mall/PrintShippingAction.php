<?php

namespace App\Filament\Actions\Mall;

use Filament\Actions\Action;

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
    }
}
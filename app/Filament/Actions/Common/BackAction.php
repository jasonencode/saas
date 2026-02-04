<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class BackAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'backAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('返回');
        $this->icon(Heroicon::ArrowLeft);
        $this->color('gray');
        $this->alpineClickHandler('window.history.back()');
    }
}
<?php

namespace App\Filament\Actions\Setting;

use App\Models\Module;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class UninstallModuleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'uninstallModule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('卸载');
        $this->color('danger');
        $this->icon(Heroicon::Trash);
        $this->hidden(fn(Module $module) => !$module->active);
    }
}

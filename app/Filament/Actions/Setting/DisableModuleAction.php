<?php

namespace App\Filament\Actions\Setting;

use App\Models\Module;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DisableModuleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'disableModule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('禁用');
        $this->color('danger');
        $this->icon(Heroicon::XCircle);
        $this->hidden(fn(Module $module) => !$module->active);

        $this->requiresConfirmation();

        $this->action(function(Module $module) {
            \Nwidart\Modules\Facades\Module::disable($module->name);

            $this->successNotificationTitle('模块禁用成功');
            $this->success();
        });
    }
}

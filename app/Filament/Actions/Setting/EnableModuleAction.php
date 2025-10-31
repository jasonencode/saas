<?php

namespace App\Filament\Actions\Setting;

use App\Models\Module;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class EnableModuleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'enableModule';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('启用');
        $this->color('success');
        $this->icon(Heroicon::CheckCircle);

        $this->hidden(fn(Module $module) => $module->active);

        $this->requiresConfirmation();

        $this->action(function(Module $module) {
            \Nwidart\Modules\Facades\Module::enable($module->name);

            $this->successNotificationTitle('模块启用成功');
            $this->success();
        });
    }
}

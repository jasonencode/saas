<?php

namespace App\Filament\Actions\Setting;

use App\Models\FailedJob;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class CleanFailedJobAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cleanFailedJob';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('清理任务');
        $this->icon(Heroicon::OutlinedTrash);
        $this->color(Color::Red);
        $this->requiresConfirmation();
        $this->visible(fn() => userCan(self::getDefaultName(), FailedJob::class));
        $this->action(function (): void {
            Artisan::call('queue:flush');
            $this->successNotificationTitle('失败任务已清理成功');
            $this->success();
        });
    }
}

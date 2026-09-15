<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\ScheduleRunLog;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

class CleanScheduleRunLogAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cleanScheduleRunLog';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('清空记录');
        $this->icon(Heroicon::OutlinedTrash);
        $this->color(Color::Red);

        $this->visible(fn (): bool => userCan(self::getDefaultName(), ScheduleRunLog::class));

        $this->requiresConfirmation();

        $this->action(function (): void {
            ScheduleRunLog::truncate();

            $this->successNotificationTitle('任务日志已清空');
            $this->success();
        });
    }
}

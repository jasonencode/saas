<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\JobBatch;
use Filament\Actions\Action;

class CancelJobBatchAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消任务');
        $this->visible(fn (JobBatch $record): bool => userCan(self::getDefaultName(), $record));
        $this->hidden(fn (JobBatch $record): bool => $record->is_finished || $record->is_cancelled);
        $this->requiresConfirmation();
        $this->action(function (JobBatch $record): void {
            $record->cancel();
            $this->successNotificationTitle('取消成功');
            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'cancelJobBatch';
    }
}

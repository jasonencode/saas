<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\JobBatch;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Artisan;

class RetryJobBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retryJobBatch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试失败任务');

        $this->visible(fn (JobBatch $record): bool => userCan(self::getDefaultName(), $record));
        $this->hidden(fn (JobBatch $record): bool => $record->failed_jobs === 0);

        $this->requiresConfirmation();

        $this->action(function (JobBatch $record): void {
            Artisan::call('queue:retry-batch '.$record->id);
            $this->successNotificationTitle('重试提交成功');
            $this->success();
        });
    }
}

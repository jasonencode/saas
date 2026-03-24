<?php

namespace App\Filament\Actions\Setting;

use App\Models\FailedJob;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class RetryFailedJobAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retryFailedJob';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试任务');
        $this->icon(Heroicon::OutlinedReceiptRefund);
        $this->visible(fn () => userCan(self::getDefaultName(), FailedJob::class));
        $this->requiresConfirmation();
        $this->action(function (): void {
            Artisan::call('queue:retry all');
            $this->successNotificationTitle('重试任务提交成功');
            $this->success();
        });
    }
}

<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\FailedJob;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class RetrySingleFailedJobAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retrySingleFailedJob';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试');
        $this->icon(Heroicon::OutlinedReceiptRefund);
        $this->visible(fn (FailedJob $record): bool => userCan(self::getDefaultName(), $record));
        $this->requiresConfirmation();
        $this->action(function (FailedJob $record): void {
            Artisan::call('queue:retry ' . $record->uuid);
            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}

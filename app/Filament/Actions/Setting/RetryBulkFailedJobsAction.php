<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\FailedJob;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class RetryBulkFailedJobsAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkRetry';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量重试');
        $this->requiresConfirmation();
        $this->visible(fn (): bool => userCan(self::getDefaultName(), FailedJob::class));
        $this->action(function (Collection $records): void {
            $uuids = implode(' ', $records->pluck('uuid')->toArray());
            Artisan::call('queue:retry ' . $uuids);
            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}

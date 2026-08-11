<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\FailedJob;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class RetryBulkFailedJobsAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'retryBulkFailedJobs';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量重试');
        $this->icon(Heroicon::OutlinedArrowPath);

        $this->visible(fn (): bool => userCan(self::getDefaultName(), FailedJob::class));

        $this->requiresConfirmation();

        $this->action(function (Collection $records): void {
            $uuids = implode(' ', $records->pluck('uuid')->toArray());
            Artisan::call('queue:retry '.$uuids);

            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}

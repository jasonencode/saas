<?php

namespace App\Filament\Actions\Content;

use App\Models\Content\AppVersion;
use App\Services\AppVersionService;
use Filament\Actions\Action;

class AppVersionPublishNowAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'appVersionPublishNow';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('立即发布版本');
        $this->visible(fn (AppVersion $record) => blank($record->publish_at) || $record->publish_at->isFuture());
        $this->action(function (AppVersion $record, Action $action, AppVersionService $service) {
            $service->publishNow($record);
            $action->successNotificationTitle('版本已发布');
            $action->success();
        });
    }
}

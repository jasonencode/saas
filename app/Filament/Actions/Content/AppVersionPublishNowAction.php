<?php

namespace App\Filament\Actions\Content;

use App\Models\Content\AppVersion;
use App\Services\Content\AppVersionService;
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

        $this->visible(fn (AppVersion $record): bool => userCan(self::getDefaultName(), $record) && $this->isPublishable($record));

        $this->action(function (AppVersion $record, AppVersionService $service): void {
            $service->publishNow($record);
            $this->successNotificationTitle('版本已发布');
            $this->success();
        });
    }

    protected function isPublishable(AppVersion $record): bool
    {
        return blank($record->publish_at) || $record->publish_at->isFuture();
    }
}

<?php

namespace App\Filament\Actions\Content;

use App\Models\AppVersion;
use App\Services\AppVersionService;
use Filament\Actions\Action;

class AppVersionUnpublishAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'appVersionUnpublish';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消版本发布');
        $this->visible(fn (AppVersion $record) => filled($record->publish_at));
        $this->action(function (AppVersion $record, Action $action, AppVersionService $service) {
            $service->unpublish($record);
            $action->successNotificationTitle('已取消版本发布');
            $action->success();
        });
    }
}

<?php

namespace App\Filament\Actions\Content;

use App\Models\Content\AppVersion;
use App\Services\Content\AppVersionService;
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
        $this->visible(fn (AppVersion $record): bool => userCan(self::getDefaultName(), $record) && filled($record->publish_at));
        $this->action(function (AppVersion $record, AppVersionService $service): void {
            $service->unpublish($record);
            $this->successNotificationTitle('已取消版本发布');
            $this->success();
        });
    }
}

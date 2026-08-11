<?php

namespace App\Filament\Actions\Content;

use App\Models\Content\AppVersion;
use App\Services\Content\AppVersionService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

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
        $this->icon(Heroicon::OutlinedEyeSlash);

        $this->visible(fn (AppVersion $record): bool => userCan(self::getDefaultName(), $record) && $this->isPublished($record));

        $this->action(function (AppVersion $record, AppVersionService $service): void {
            $service->unpublish($record);
            $this->successNotificationTitle('已取消版本发布');
            $this->success();
        });
    }

    protected function isPublished(AppVersion $record): bool
    {
        return filled($record->publish_at);
    }
}

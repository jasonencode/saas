<?php

namespace App\Filament\Actions\Content;

use App\Models\AppVersion;
use Filament\Actions\Action;

class PublishNowAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'publishNow';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('立即发布');
        $this->visible(fn (AppVersion $record) => blank($record->publish_at) || $record->publish_at->isFuture());
        $this->action(function (AppVersion $record, Action $action) {
            $record->publish_at = now();
            $record->save();
            $action->successNotificationTitle('已发布');
            $action->success();
        });
    }
}


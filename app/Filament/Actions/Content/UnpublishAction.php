<?php

namespace App\Filament\Actions\Content;

use App\Models\AppVersion;
use Filament\Actions\Action;

class UnpublishAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unpublish';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消发布');
        $this->visible(fn(AppVersion $record) => filled($record->publish_at));
        $this->action(function(AppVersion $record, Action $action) {
            $record->publish_at = null;
            $record->save();
            $action->successNotificationTitle('已取消发布');
            $action->success();
        });
    }
}


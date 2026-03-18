<?php

namespace App\Filament\Actions\Content;

use App\Models\AppVersion;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;

class SchedulePublishAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'schedulePublish';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('计划发布');
        $this->modalWidth('md');
        $this->schema([
            DateTimePicker::make('publish_at')
                ->label('发布时间')
                ->required(),
        ]);
        $this->action(function (array $data, AppVersion $record, Action $action) {
            $record->publish_at = $data['publish_at'];
            $record->save();
            $action->successNotificationTitle('计划已设置');
            $action->success();
        });
    }
}


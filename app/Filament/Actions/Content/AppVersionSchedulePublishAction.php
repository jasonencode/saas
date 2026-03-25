<?php

namespace App\Filament\Actions\Content;

use App\Models\AppVersion;
use App\Services\AppVersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;

class AppVersionSchedulePublishAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'appVersionSchedulePublish';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('计划发布版本');
        $this->modalWidth('md');
        $this->schema([
            DateTimePicker::make('publish_at')
                ->label('发布时间')
                ->required(),
        ]);
        $this->action(function (array $data, AppVersion $record, Action $action, AppVersionService $service) {
            $service->schedulePublish($record, $data['publish_at']);
            $action->successNotificationTitle('发布计划已设置');
            $action->success();
        });
    }
}

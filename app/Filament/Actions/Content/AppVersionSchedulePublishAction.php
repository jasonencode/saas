<?php

namespace App\Filament\Actions\Content;

use App\Models\Content\AppVersion;
use App\Services\Content\AppVersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Enums\Width;

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

        $this->visible(fn (AppVersion $record): bool => userCan(self::getDefaultName(), $record));

        $this->modalWidth(Width::Medium);

        $this->schema([
            DateTimePicker::make('publish_at')
                ->label('发布时间')
                ->required(),
        ]);

        $this->action(function (array $data, AppVersion $record, AppVersionService $service): void {
            $service->schedulePublish($record, $data['publish_at']);
            $this->successNotificationTitle('发布计划已设置');
            $this->success();
        });
    }
}

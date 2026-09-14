<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\ScheduleRunLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewScheduleRunLog extends ViewRecord
{
    protected static string $resource = ScheduleRunLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}

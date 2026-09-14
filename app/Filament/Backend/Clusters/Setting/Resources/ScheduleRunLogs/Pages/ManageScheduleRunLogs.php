<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\ScheduleRunLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageScheduleRunLogs extends ManageRecords
{
    protected static string $resource = ScheduleRunLogResource::class;
}

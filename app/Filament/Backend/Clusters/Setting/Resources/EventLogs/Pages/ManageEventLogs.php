<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\EventLogs\EventLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageEventLogs extends ManageRecords
{
    protected static string $resource = EventLogResource::class;
}

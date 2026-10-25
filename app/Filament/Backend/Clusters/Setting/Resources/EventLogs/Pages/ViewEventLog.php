<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Setting\Resources\EventLogs\EventLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEventLog extends ViewRecord
{
    protected static string $resource = EventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}

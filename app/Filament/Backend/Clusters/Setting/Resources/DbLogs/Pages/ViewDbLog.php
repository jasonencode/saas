<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\DbLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDbLog extends ViewRecord
{
    protected static string $resource = DbLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}

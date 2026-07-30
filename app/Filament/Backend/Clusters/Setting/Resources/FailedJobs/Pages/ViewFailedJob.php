<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\FailedJobResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFailedJob extends ViewRecord
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}

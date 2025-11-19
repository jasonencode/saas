<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Pages;

use App\Filament\Actions\Setting\CleanFailedJobAction;
use App\Filament\Actions\Setting\RetryFailedJobAction;
use App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\FailedJobResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFailedJobs extends ManageRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CleanFailedJobAction::make(),
            RetryFailedJobAction::make(),
        ];
    }
}

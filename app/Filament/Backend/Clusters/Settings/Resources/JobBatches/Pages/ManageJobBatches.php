<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\JobBatches\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\JobBatches\JobBatchResource;
use Filament\Resources\Pages\ManageRecords;

class ManageJobBatches extends ManageRecords
{
    protected static string $resource = JobBatchResource::class;
}

<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\ApiLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageApiLogs extends ManageRecords
{
    protected static string $resource = ApiLogResource::class;
}

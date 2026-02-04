<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\ApiLogResource;
use Filament\Resources\Pages\ListRecords;

class ManageApiLogs extends ListRecords
{
    protected static string $resource = ApiLogResource::class;
}

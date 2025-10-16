<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Exports\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\Exports\ExportResource;
use Filament\Resources\Pages\ManageRecords;

class ManageExports extends ManageRecords
{
    protected static string $resource = ExportResource::class;
}

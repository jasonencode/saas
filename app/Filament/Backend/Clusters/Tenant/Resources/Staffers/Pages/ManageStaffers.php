<?php

namespace App\Filament\Backend\Clusters\Tenant\Resources\Staffers\Pages;

use App\Filament\Backend\Clusters\Tenant\Resources\Staffers\StafferResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;
}

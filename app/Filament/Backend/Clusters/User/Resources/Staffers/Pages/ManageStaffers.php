<?php

namespace App\Filament\Backend\Clusters\User\Resources\Staffers\Pages;

use App\Filament\Backend\Clusters\User\Resources\Staffers\StafferResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;
}

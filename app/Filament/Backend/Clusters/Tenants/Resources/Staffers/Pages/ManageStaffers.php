<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Staffers\Pages;

use App\Filament\Backend\Clusters\Tenants\Resources\Staffers\StafferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;
}

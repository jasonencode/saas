<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\PickupPointResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePickupPoints extends ManageRecords
{
    protected static string $resource = PickupPointResource::class;
}

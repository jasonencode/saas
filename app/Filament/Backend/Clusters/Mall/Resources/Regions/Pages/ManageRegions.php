<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Regions\RegionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRegions extends ManageRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

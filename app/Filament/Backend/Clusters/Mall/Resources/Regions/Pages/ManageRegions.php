<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Regions\RegionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

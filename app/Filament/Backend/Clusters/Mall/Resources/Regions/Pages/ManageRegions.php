<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Regions\RegionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Kisame76\FilamentTreeTable\Concerns\InteractsWithExpandableRows;
use Kisame76\FilamentTreeTable\Contracts\HasExpandableRows;

class ManageRegions extends ManageRecords implements HasExpandableRows
{
    use InteractsWithExpandableRows;

    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

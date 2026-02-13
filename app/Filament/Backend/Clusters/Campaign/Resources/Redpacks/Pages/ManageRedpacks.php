<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Pages;

use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageRedpacks extends ListRecords
{
    protected static string $resource = RedpackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

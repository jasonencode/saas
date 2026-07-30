<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Pages;

use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRedpacks extends ManageRecords
{
    protected static string $resource = RedpackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

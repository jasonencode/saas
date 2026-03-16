<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\Pages;

use App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRedpacks extends ManageRecords
{
    protected static string $resource = RedpackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

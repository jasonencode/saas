<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\PickupPoints\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\PickupPoints\PickupPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePickupPoints extends ManageRecords
{
    protected static string $resource = PickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

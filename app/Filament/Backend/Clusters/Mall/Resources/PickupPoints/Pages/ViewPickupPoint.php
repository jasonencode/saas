<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\PickupPointResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPickupPoint extends ViewRecord
{
    protected static string $resource = PickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}

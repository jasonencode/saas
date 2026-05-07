<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Deliveries\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Mall\Resources\Deliveries\DeliveryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}

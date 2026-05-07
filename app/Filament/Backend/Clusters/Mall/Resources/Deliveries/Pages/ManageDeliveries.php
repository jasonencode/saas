<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Deliveries\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Deliveries\DeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ManageDeliveries extends ListRecords
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

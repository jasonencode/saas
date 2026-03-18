<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Addresses\AddressResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAddresses extends ManageRecords
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

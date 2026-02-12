<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\AddressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageAddresses extends ListRecords
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

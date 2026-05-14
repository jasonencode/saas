<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Addresses\Pages;

use App\Filament\Tenant\Clusters\BlockChain\Resources\Addresses\AddressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAddresses extends ManageRecords
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

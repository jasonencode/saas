<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\Pages;

use App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\NetworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNetworks extends ManageRecords
{
    protected static string $resource = NetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\Networks\NetworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNetworks extends ManageRecords
{
    protected static string $resource = NetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

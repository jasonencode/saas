<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
